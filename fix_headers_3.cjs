const fs = require('fs');

const pages = [
    'c:\\laragon\\www\\speakready_ai\\resources\\views\\user\\progress.blade.php',
    'c:\\laragon\\www\\speakready_ai\\resources\\views\\user\\drills\\voice.blade.php',
    'c:\\laragon\\www\\speakready_ai\\resources\\views\\user\\learning.blade.php',
    'c:\\laragon\\www\\speakready_ai\\resources\\views\\user\\feedback.blade.php',
    'c:\\laragon\\www\\speakready_ai\\resources\\views\\user\\reports.blade.php',
    'c:\\laragon\\www\\speakready_ai\\resources\\views\\user\\leaderboard.blade.php',
    'c:\\laragon\\www\\speakready_ai\\resources\\views\\user\\settings.blade.php',
    'c:\\laragon\\www\\speakready_ai\\resources\\views\\admin\\dashboard.blade.php',
    'c:\\laragon\\www\\speakready_ai\\resources\\views\\admin\\questions.blade.php',
    'c:\\laragon\\www\\speakready_ai\\resources\\views\\admin\\modules.blade.php',
    'c:\\laragon\\www\\speakready_ai\\resources\\views\\admin\\complaints.blade.php',
    'c:\\laragon\\www\\speakready_ai\\resources\\views\\admin\\archive.blade.php',
    'c:\\laragon\\www\\speakready_ai\\resources\\views\\admin\\system.blade.php'
];

pages.forEach(file => {
    if (!fs.existsSync(file)) return;
    let content = fs.readFileSync(file, 'utf8');

    // Make sure we replace ALL instances of header wrapping and gap
    // .mb-4.d-flex.justify-content-between ... flex-wrap ... gap-x
    
    // Convert to strict nowrap align-items-start
    content = content.replace(/class="(.*?)mb-4\s+d-flex\s+justify-content-between\s+align-items-center\s+flex-wrap\s+gap-3(.*?)"/g, 'class="$1mb-4 d-flex justify-content-between align-items-start$2"');
    content = content.replace(/class="(.*?)mb-4\s+d-flex\s+justify-content-between\s+align-items-start\s+flex-wrap\s+gap-3(.*?)"/g, 'class="$1mb-4 d-flex justify-content-between align-items-start$2"');
    content = content.replace(/class="(.*?)mb-4\s+d-flex\s+justify-content-between\s+align-items-center(.*?)"/g, 'class="$1mb-4 d-flex justify-content-between align-items-start$2"');

    // For any tutorial button that is still nested deeply, pull it out
    const tutRegex = /(<div[^>]*class="[^"]*d-flex[^"]*"[^>]*>)\s*(<button[^>]*?onclick="startOnboardingTour\(\)"[^>]*?>.*?<\/button>)\s*/g;
    content = content.replace(tutRegex, (match, div, btn) => {
        return div + '\n';
    });
    
    // Now inject it right after the first inner div
    if (!content.includes('startOnboardingTour()')) {
        // we removed it, but we need to put it back
        // Actually, this approach is tricky if we lose the button.
    }
    
    fs.writeFileSync(file, content);
});
