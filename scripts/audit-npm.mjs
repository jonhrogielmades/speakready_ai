import { spawnSync } from 'node:child_process';
import { readFileSync } from 'node:fs';

// picomatch has no patched npm release for GHSA-fc5q-39xc-vjpf yet. It is used
// only by development build tools here. Keep auditing every other high finding.
const unpatchedBuildToolAdvisory = 'https://github.com/advisories/GHSA-fc5q-39xc-vjpf';

const npmCommand = process.platform === 'win32' ? 'cmd.exe' : 'npm';
const npmArguments = process.platform === 'win32' ? ['/d', '/s', '/c', 'npm audit --json'] : ['audit', '--json'];
const audit = spawnSync(npmCommand, npmArguments, {
    encoding: 'utf8',
    maxBuffer: 5 * 1024 * 1024,
});

let report;
try {
    report = JSON.parse(audit.stdout);
} catch {
    console.error(audit.stderr || audit.error || 'npm audit did not return valid JSON.');
    process.exit(1);
}

if (audit.error || ![0, 1].includes(audit.status) || report.error || !report.vulnerabilities) {
    console.error(audit.stderr || audit.error || report.error || 'npm audit failed.');
    process.exit(1);
}

const vulnerabilities = report.vulnerabilities;
const lockedPackages = JSON.parse(readFileSync(new URL('../package-lock.json', import.meta.url), 'utf8')).packages;

function comesOnlyFromUnpatchedPicomatch(packageName, visited = new Set()) {
    const finding = vulnerabilities[packageName];
    if (!finding || visited.has(packageName) || !Array.isArray(finding.via) || finding.via.length === 0
        || !Array.isArray(finding.nodes) || finding.nodes.length === 0
        || !finding.nodes.every((node) => lockedPackages[node]?.dev === true)) {
        return false;
    }

    const path = new Set(visited);
    path.add(packageName);

    return finding.via.every((source) => typeof source === 'string'
        ? comesOnlyFromUnpatchedPicomatch(source, path)
        : source?.name === 'picomatch' && source.url === unpatchedBuildToolAdvisory);
}

const blocking = Object.entries(vulnerabilities)
    .filter(([name, finding]) => ['high', 'critical'].includes(finding.severity)
        && !comesOnlyFromUnpatchedPicomatch(name));

if (blocking.length > 0) {
    console.error(`Blocking npm audit findings: ${blocking.map(([name]) => name).join(', ')}`);
    process.exit(1);
}

const excepted = Object.keys(vulnerabilities).filter((name) => comesOnlyFromUnpatchedPicomatch(name));
if (excepted.length > 0) {
    console.log(`Allowed unpatched build-tool advisory ${unpatchedBuildToolAdvisory} in: ${excepted.join(', ')}`);
}
console.log('No other high or critical npm audit findings.');
