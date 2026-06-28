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

    // Remove the bad CSS hack in admin-mobile if present (handled elsewhere but good to be safe)
    
    // We want to transform:
    /*
    <div class="mb-4 d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div>
            <h4>...</h4>
            <p>...</p>
        </div>
        <div class="d-flex gap-2 flex-wrap align-items-center">
            <button class="btn btn-sm d-inline-flex align-items-center"...>Replay Tutorial</button>
            <button ...>Export PDF</button>
            <button ...>Export Excel</button>
        </div>
    </div>
    */
    
    // Replace the main container to not have gap-3 on mobile and use align-items-start
    content = content.replace(/<div class="mb-4 d-flex justify-content-between align-items-(center|start) flex-wrap gap-3"(.*?)>/g, '<div class="mb-4 d-flex justify-content-between align-items-start"$2>');
    
    // Find the tutorial button and move it next to the text div
    const tutorialRegex = /<button[^>]*?onclick="startOnboardingTour\(\)"[^>]*?>.*?<\/button>/s;
    const matchTutorial = content.match(tutorialRegex);
    if (matchTutorial) {
        // Remove tutorial button from its original place
        content = content.replace(tutorialRegex, '');
        
        // Add it directly after the text div
        // The text div is the first <div> after the main header div
        // We can just find the first </div> after <div class="mb-4 d-flex justify-content-between align-items-start">
        
        const headerStart = content.indexOf('<div class="mb-4 d-flex justify-content-between align-items-start"');
        if (headerStart !== -1) {
            let innerDivStart = content.indexOf('<div>', headerStart);
            if (innerDivStart !== -1) {
                let innerDivEnd = content.indexOf('</div>', innerDivStart);
                if (innerDivEnd !== -1) {
                    let before = content.substring(0, innerDivEnd + 6);
                    let after = content.substring(innerDivEnd + 6);
                    
                    // Modify tutorial button to ensure it doesn't wrap and stays on right
                    let tutBtn = matchTutorial[0].replace('class="btn', 'class="btn flex-shrink-0 ms-3');
                    
                    content = before + '\n        ' + tutBtn + after;
                }
            }
        }
    }
    
    // Now whatever is left in the right div needs to be moved to a new row below the header
    // The right div was `<div class="d-flex gap-2 flex-wrap align-items-center">` or similar
    // Sometimes it just contained the tutorial button and is now empty!
    content = content.replace(/<div[^>]*class="[^"]*d-flex gap-2[^"]*"[^>]*>\s*<\/div>/s, ''); // Remove if empty
    content = content.replace(/<div[^>]*class="[^"]*d-flex gap-2[^"]*"[^>]*>(.*?)<\/div>/s, '<div class="header-actions-row">$1</div>');
    
    fs.writeFileSync(file, content);
});

console.log("Done transforming headers");
