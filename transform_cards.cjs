const fs = require('fs');
const file = 'c:\\laragon\\www\\speakready_ai\\resources\\views\\admin\\settings.blade.php';
let content = fs.readFileSync(file, 'utf8');

// 1. Redesign Cards
const colors = ['#3b82f6', '#10b981', '#f59e0b', '#ec4899', '#8b5cf6', '#14b8a6', '#f43f5e', '#0ea5e9'];

content = content.replace(/<div class="col-6 col-md-4 col-lg-3">\s*<button type="button" class="btn w-100 p-4 premium-card text-center text-white h-100"(.*?)data-bs-target="#modal-(\d+)">\s*<i class="(.*?) fa-2x mb-3" style="color: #3b82f6;"><\/i><br>\s*<span class="fw-semibold">(.*?)<\/span>\s*<\/button>\s*<\/div>/gs, (match, attrs, id, iconClass, title) => {
    const color = colors[(parseInt(id) - 1) % colors.length];
    
    return `<div class="col-6 col-md-4 col-lg-3">\n        <button type="button" class="btn w-100 h-100 d-flex flex-column align-items-center justify-content-center" style="background: var(--sf, #1e1e2d); border: 1px solid var(--bd, rgba(255,255,255,0.05)); border-radius: 20px; box-shadow: 0 8px 24px rgba(0,0,0,0.05); min-height: 200px; transition: all 0.3s; padding: 20px; position: relative; overflow: hidden;" onmouseover="this.style.transform='translateY(-5px)'; this.style.boxShadow='0 12px 30px rgba(0,0,0,0.15)'; this.style.borderColor='${color}40';" onmouseout="this.style.transform='none'; this.style.boxShadow='0 8px 24px rgba(0,0,0,0.05)'; this.style.borderColor='var(--bd, rgba(255,255,255,0.05))';" data-bs-toggle="modal" data-bs-target="#modal-${id}">\n            <!-- Background glow -->\n            <div style="position: absolute; top: -50px; right: -50px; width: 100px; height: 100px; background: ${color}; filter: blur(50px); opacity: 0.2; border-radius: 50%;"><\/div>\n            \n            <div style="width: 64px; height: 64px; border-radius: 18px; background: ${color}15; display: flex; align-items: center; justify-content: center; margin-bottom: 24px; box-shadow: inset 0 0 0 1px ${color}30;">\n                <i class="${iconClass} fa-2x" style="color: ${color};"><\/i>\n            <\/div>\n            <span class="fw-bold mb-2" style="font-size: 1.15rem; color: var(--tx, #fff);">${title}<\/span>\n            <span style="font-size: 0.75rem; font-weight: 700; color: var(--tx3, #888); letter-spacing: 1.5px; text-transform: uppercase;">Settings<\/span>\n        <\/button>\n    <\/div>`;
});

// 2. Add Modal Footer
const footerHtml = `            <\/div>
            <div class="modal-footer border-top-0 pt-3" style="border-color: var(--bd, rgba(255,255,255,0.1)) !important;">
                <button type="button" class="btn px-4" data-bs-dismiss="modal" style="background: rgba(255,255,255,0.05); color: var(--tx2, #ccc); border-radius: 12px; font-weight: 600;">Cancel<\/button>
                <button type="submit" class="btn btn-primary px-4" style="background: #3b82f6; border: none; border-radius: 12px; font-weight: 600; box-shadow: 0 4px 12px rgba(59,130,246,0.3);"><i class="fa-solid fa-floppy-disk me-2"><\/i>Save Changes<\/button>
            <\/div>
        <\/div>
    <\/div>
<\/div>`;

content = content.replace(/\s*<\/div>\n        <\/div>\n    <\/div>\n<\/div>/g, '\n' + footerHtml);

// Fix X button styling if needed
// User asked "and for the modal form and X button"
content = content.replace(/<button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"><\/button>/g, '<button type="button" class="btn-close btn-close-white opacity-75" data-bs-dismiss="modal" aria-label="Close" style="background-size: 0.8em;"><\/button>');

// 3. Remove Sticky global save button
const stickyRegex = /<!-- Save Button Sticky -->\s*<div class="col-12">\s*<div class="btn-save-fixed shadow w-100">.*?<\/div>\s*<\/div>/s;
content = content.replace(stickyRegex, '');

fs.writeFileSync('c:\\laragon\\www\\speakready_ai\\resources\\views\\admin\\settings_cards.blade.php', content, 'utf8');
console.log('Success');
