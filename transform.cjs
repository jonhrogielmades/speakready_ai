const fs = require('fs');
const file = 'c:\\laragon\\www\\speakready_ai\\resources\\views\\admin\\settings.blade.php';
let content = fs.readFileSync(file, 'utf8');

// 1. Find nav items
const navRegex = /<button class="nav-link.*?data-bs-target="#tab-(\d+)".*?><i class="(.*?)"><\/i> (.*?)<\/button>/g;
let match;
const navItems = [];
while ((match = navRegex.exec(content)) !== null) {
    navItems.push({ id: match[1], icon: match[2], title: match[3].trim() });
}

// 2. Build the new grid HTML
let gridHtml = `<div class="col-12 mb-4">\n    <div class="row g-3">`;
for (const item of navItems) {
    gridHtml += `\n        <div class="col-6 col-md-4 col-lg-3">\n            <button type="button" class="btn w-100 p-4 premium-card text-center text-white h-100" style="transition: all 0.3s;" onmouseover="this.style.background='rgba(255,255,255,0.05)'; this.style.borderColor='var(--pur, #3b82f6)'" onmouseout="this.style.background='var(--sf, #1e1e2d)'; this.style.borderColor='var(--bd, rgba(255,255,255,0.1))'" data-bs-toggle="modal" data-bs-target="#modal-${item.id}">\n                <i class="${item.icon} fa-2x mb-3" style="color: #3b82f6;"><\/i><br>\n                <span class="fw-semibold">${item.title}<\/span>\n            <\/button>\n        <\/div>`;
}
gridHtml += `\n    <\/div>\n<\/div>`;

// Replace sidebar navigation
const sidebarRegex = /<!-- Sidebar Navigation -->.*?<\/div>\s*<\/div>\s*<!-- Tab Content -->\s*<div class="col-lg-9 col-xl-10 pb-5">\s*<div class="tab-content" id="settings-tabContent">/s;
content = content.replace(sidebarRegex, '<!-- Settings Grid -->\n' + gridHtml);

// Replace Tab Pane Starts
content = content.replace(/<div class="tab-pane fade.*?" id="tab-(\d+)" role="tabpanel">\s*<div class="premium-card">\s*<h5 class="fw-bold mb-4".*?<\/h5>/gs, (match, p1) => {
    const title = navItems.find(i => i.id === p1)?.title || `Settings ${p1}`;
    return `<!-- Modal ${p1} -->\n<div class="modal fade" id="modal-${p1}" tabindex="-1" aria-hidden="true">\n    <div class="modal-dialog modal-lg modal-dialog-centered">\n        <div class="modal-content" style="background: var(--sf, #1e1e2d); border: 1px solid var(--bd, rgba(255, 255, 255, 0.1)); border-radius: 16px;">\n            <div class="modal-header border-bottom-0">\n                <h5 class="modal-title text-white fw-bold">${title}<\/h5>\n                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"><\/button>\n            <\/div>\n            <div class="modal-body pt-0">`;
});

// Replace Tab Pane Ends
// Originally it's </div></div> before the next <!-- X. Settings --> or </div></div> before <!-- Save Button Sticky -->
// Since we removed `<div class="premium-card">` and `<div class="tab-pane">`
// They need to be closed with `</div></div></div></div>` (modal-body, modal-content, modal-dialog, modal)
// Wait, let's just do a string replacement of `</div>\n                    </div>` with `</div></div></div></div>`
content = content.replace(/\s*<\/div>\s*<\/div>\s*<!-- (\d+)\./gs, '\n            </div>\n        </div>\n    </div>\n</div>\n\n<!-- $1.');

// The last tab pane end
content = content.replace(/\s*<\/div>\s*<\/div>\s*<\/div>\s*<\/div>\s*<!-- Save Button Sticky -->/s, '\n            </div>\n        </div>\n    </div>\n</div>\n\n<!-- Save Button Sticky -->');

fs.writeFileSync('c:\\laragon\\www\\speakready_ai\\resources\\views\\admin\\settings_new.blade.php', content, 'utf8');
console.log('Saved to settings_new.blade.php');
