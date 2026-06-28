const fs = require('fs');

const pages = [
    'c:\\laragon\\www\\speakready_ai\\resources\\views\\user\\progress.blade.php',
    'c:\\laragon\\www\\speakready_ai\\resources\\views\\user\\drills\\voice.blade.php',
    'c:\\laragon\\www\\speakready_ai\\resources\\views\\user\\learning.blade.php',
    'c:\\laragon\\www\\speakready_ai\\resources\\views\\user\\feedback.blade.php',
    'c:\\laragon\\www\\speakready_ai\\resources\\views\\user\\reports.blade.php',
    'c:\\laragon\\www\\speakready_ai\\resources\\views\\user\\leaderboard.blade.php'
];

pages.forEach(file => {
    if (!fs.existsSync(file)) return;
    let content = fs.readFileSync(file, 'utf8');

    // We want to pull `<div class="header-actions-row"> ... </div>` OUT of the `<div class="mb-4 d-flex justify-content-between align-items-start">` div, so it goes directly below it.
    
    // Find header-actions-row
    const regex = /(<div class="mb-4 d-flex justify-content-between align-items-start">.*?)(<div class="header-actions-row">.*?<\/div>)\s*<\/div>/s;
    const match = content.match(regex);
    if (match) {
        let beforeActions = match[1];
        let actions = match[2];
        
        let newHeader = beforeActions + '\n    </div>\n    ' + actions;
        content = content.replace(regex, newHeader);
        fs.writeFileSync(file, content);
    }
});

console.log("Fixed headers part 2");
