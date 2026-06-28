const fs = require('fs');
const file = 'c:\\laragon\\www\\speakready_ai\\resources\\views\\layouts\\app-mobile.blade.php';
let content = fs.readFileSync(file, 'utf8');

// Replace the old progress buttons block
const regex1 = /\/\* --- Progress page export buttons ---\*\/[\s\S]*?\/\* --- Fix search input width in table headers ---\*\//;
const newCss1 = `/* --- Global Header & Button Responsiveness (Progress, Feedback, Reports, Leaderboard) --- */
         @media (max-width: 767px) {
            .mb-4.d-flex.justify-content-between {
               flex-direction: column !important;
               align-items: stretch !important;
               gap: 15px !important;
            }
            .mb-4.d-flex.justify-content-between > div.d-flex.gap-2 {
               flex-direction: column !important;
               width: 100% !important;
            }
            .mb-4.d-flex.justify-content-between .btn {
               width: 100% !important;
               justify-content: center !important;
               margin: 0 !important;
            }
            
            /* Filters in Feedback and Reports */
            #feedback-filters, .filters-container {
               flex-direction: column !important;
               width: 100% !important;
            }
            #feedback-filters > *, .filters-container > * {
               width: 100% !important;
               max-width: 100% !important;
               margin-bottom: 8px !important;
            }
            .input-group, .form-select {
               width: 100% !important;
            }
            
            /* Make sure charts don't cause horizontal scroll */
            canvas {
               max-width: 100% !important;
               height: auto !important;
            }
         }

         /* --- Fix search input width in table headers --- */`;

if (content.match(regex1)) {
    content = content.replace(regex1, newCss1);
} else {
    console.log("Could not find regex1");
}

fs.writeFileSync(file, content);
console.log("Done");
