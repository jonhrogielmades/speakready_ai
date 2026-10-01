from __future__ import annotations

from pathlib import Path

from docx import Document
from docx.enum.section import WD_SECTION
from docx.enum.table import WD_ALIGN_VERTICAL
from docx.enum.text import WD_ALIGN_PARAGRAPH
from docx.oxml import OxmlElement
from docx.oxml.ns import qn
from docx.shared import Inches, Pt, RGBColor


ROOT = Path(__file__).resolve().parents[1]
OUTPUT = ROOT / "output" / "SpeakReady_AI_Chapter_4_PWA_Desktop_Mobile_Format.docx"


def set_cell_shading(cell, fill: str) -> None:
    tc_pr = cell._tc.get_or_add_tcPr()
    shd = tc_pr.find(qn("w:shd"))
    if shd is None:
        shd = OxmlElement("w:shd")
        tc_pr.append(shd)
    shd.set(qn("w:fill"), fill)


def set_cell_border(cell, color: str = "D9D9D9", size: str = "6") -> None:
    tc = cell._tc
    tc_pr = tc.get_or_add_tcPr()
    borders = tc_pr.first_child_found_in("w:tcBorders")
    if borders is None:
        borders = OxmlElement("w:tcBorders")
        tc_pr.append(borders)
    for edge in ("top", "left", "bottom", "right", "insideH", "insideV"):
        tag = "w:{}".format(edge)
        element = borders.find(qn(tag))
        if element is None:
            element = OxmlElement(tag)
            borders.append(element)
        element.set(qn("w:val"), "single")
        element.set(qn("w:sz"), size)
        element.set(qn("w:space"), "0")
        element.set(qn("w:color"), color)


def set_cell_margins(cell, top=90, start=120, bottom=90, end=120) -> None:
    tc_pr = cell._tc.get_or_add_tcPr()
    tc_mar = tc_pr.first_child_found_in("w:tcMar")
    if tc_mar is None:
        tc_mar = OxmlElement("w:tcMar")
        tc_pr.append(tc_mar)
    for margin_name, value in (
        ("top", top),
        ("start", start),
        ("bottom", bottom),
        ("end", end),
    ):
        node = tc_mar.find(qn(f"w:{margin_name}"))
        if node is None:
            node = OxmlElement(f"w:{margin_name}")
            tc_mar.append(node)
        node.set(qn("w:w"), str(value))
        node.set(qn("w:type"), "dxa")


def set_repeat_table_header(row) -> None:
    tr_pr = row._tr.get_or_add_trPr()
    tbl_header = OxmlElement("w:tblHeader")
    tbl_header.set(qn("w:val"), "true")
    tr_pr.append(tbl_header)


def style_table(table, widths=None, header_fill="1F4E79", header_text="FFFFFF") -> None:
    table.autofit = False
    for row_idx, row in enumerate(table.rows):
        for col_idx, cell in enumerate(row.cells):
            set_cell_border(cell)
            set_cell_margins(cell)
            cell.vertical_alignment = WD_ALIGN_VERTICAL.CENTER
            if widths and col_idx < len(widths):
                cell.width = widths[col_idx]
            for paragraph in cell.paragraphs:
                paragraph.paragraph_format.space_after = Pt(0)
                paragraph.paragraph_format.line_spacing = 1.05
                for run in paragraph.runs:
                    run.font.name = "Times New Roman"
                    run._element.rPr.rFonts.set(qn("w:ascii"), "Times New Roman")
                    run._element.rPr.rFonts.set(qn("w:hAnsi"), "Times New Roman")
                    run.font.size = Pt(10)
            if row_idx == 0:
                set_cell_shading(cell, header_fill)
                for paragraph in cell.paragraphs:
                    paragraph.alignment = WD_ALIGN_PARAGRAPH.CENTER
                    for run in paragraph.runs:
                        run.bold = True
                        run.font.color.rgb = RGBColor.from_string(header_text)
            elif row_idx % 2 == 0:
                set_cell_shading(cell, "F7FAFD")
    if table.rows:
        set_repeat_table_header(table.rows[0])


def set_column_widths(table, widths) -> None:
    for row in table.rows:
        for idx, width in enumerate(widths):
            if idx < len(row.cells):
                row.cells[idx].width = width


def add_run(paragraph, text: str, bold=False, italic=False):
    run = paragraph.add_run(text)
    run.bold = bold
    run.italic = italic
    run.font.name = "Times New Roman"
    run._element.rPr.rFonts.set(qn("w:ascii"), "Times New Roman")
    run._element.rPr.rFonts.set(qn("w:hAnsi"), "Times New Roman")
    run.font.size = Pt(12)
    return run


def add_body_paragraph(doc, text: str = "", alignment=None):
    paragraph = doc.add_paragraph()
    paragraph.style = doc.styles["Normal"]
    paragraph.paragraph_format.first_line_indent = Inches(0.5)
    paragraph.paragraph_format.alignment = alignment or WD_ALIGN_PARAGRAPH.JUSTIFY
    paragraph.paragraph_format.line_spacing = 1.5
    paragraph.paragraph_format.space_after = Pt(6)
    add_run(paragraph, text)
    return paragraph


def add_heading(doc, text: str, level: int = 1):
    paragraph = doc.add_paragraph()
    paragraph.style = doc.styles[f"Heading {level}"]
    paragraph.paragraph_format.space_before = Pt(12 if level == 1 else 8)
    paragraph.paragraph_format.space_after = Pt(6)
    paragraph.paragraph_format.keep_with_next = True
    run = add_run(paragraph, text, bold=True)
    run.font.color.rgb = RGBColor(0, 0, 0)
    if level == 1:
        paragraph.alignment = WD_ALIGN_PARAGRAPH.CENTER
        run.font.size = Pt(14)
    else:
        paragraph.alignment = WD_ALIGN_PARAGRAPH.LEFT
        run.font.size = Pt(12)
    return paragraph


def add_caption(doc, text: str):
    paragraph = doc.add_paragraph()
    paragraph.paragraph_format.space_before = Pt(8)
    paragraph.paragraph_format.space_after = Pt(3)
    paragraph.paragraph_format.keep_with_next = True
    run = add_run(paragraph, text, bold=True)
    run.font.size = Pt(11)
    return paragraph


def add_placeholder_line(doc, label: str):
    paragraph = doc.add_paragraph()
    paragraph.paragraph_format.first_line_indent = Inches(0)
    paragraph.paragraph_format.space_before = Pt(3)
    paragraph.paragraph_format.space_after = Pt(8)
    paragraph.paragraph_format.line_spacing = 1.15
    paragraph.alignment = WD_ALIGN_PARAGRAPH.CENTER
    run = add_run(paragraph, f"[Insert {label} here]", italic=True)
    run.font.size = Pt(11)
    return paragraph


def add_bullets(doc, items):
    for item in items:
        paragraph = doc.add_paragraph(style="List Bullet")
        paragraph.paragraph_format.line_spacing = 1.15
        paragraph.paragraph_format.space_after = Pt(3)
        for run in paragraph.runs:
            run.font.name = "Times New Roman"
        if not paragraph.runs:
            add_run(paragraph, item)
        else:
            paragraph.runs[0].text = item


def add_table(doc, headers, rows, widths=None, header_fill="1F4E79"):
    table = doc.add_table(rows=1, cols=len(headers))
    hdr = table.rows[0].cells
    for idx, header in enumerate(headers):
        hdr[idx].text = header
    for row_values in rows:
        row = table.add_row().cells
        for idx, value in enumerate(row_values):
            row[idx].text = value
    if widths:
        set_column_widths(table, widths)
    style_table(table, widths=widths, header_fill=header_fill)
    doc.add_paragraph().paragraph_format.space_after = Pt(3)
    return table


def setup_document() -> Document:
    doc = Document()
    section = doc.sections[0]
    section.page_width = Inches(8.5)
    section.page_height = Inches(11)
    section.top_margin = Inches(1)
    section.bottom_margin = Inches(1)
    section.left_margin = Inches(1)
    section.right_margin = Inches(1)

    styles = doc.styles
    styles["Normal"].font.name = "Times New Roman"
    styles["Normal"]._element.rPr.rFonts.set(qn("w:ascii"), "Times New Roman")
    styles["Normal"]._element.rPr.rFonts.set(qn("w:hAnsi"), "Times New Roman")
    styles["Normal"].font.size = Pt(12)
    for style_name in ("Heading 1", "Heading 2", "Title"):
        style = styles[style_name]
        style.font.name = "Times New Roman"
        style._element.rPr.rFonts.set(qn("w:ascii"), "Times New Roman")
        style._element.rPr.rFonts.set(qn("w:hAnsi"), "Times New Roman")
        style.font.color.rgb = RGBColor(0, 0, 0)
    return doc


def build_document() -> None:
    doc = setup_document()

    title = doc.add_paragraph()
    title.style = doc.styles["Title"]
    title.alignment = WD_ALIGN_PARAGRAPH.CENTER
    title.paragraph_format.space_after = Pt(0)
    title.paragraph_format.keep_with_next = True
    run = add_run(title, "CHAPTER 4", bold=True)
    run.font.size = Pt(16)

    subtitle = doc.add_paragraph()
    subtitle.alignment = WD_ALIGN_PARAGRAPH.CENTER
    subtitle.paragraph_format.space_after = Pt(18)
    subtitle.paragraph_format.keep_with_next = True
    run = add_run(subtitle, "RESULTS AND DISCUSSION", bold=True)
    run.font.size = Pt(16)

    add_body_paragraph(
        doc,
        "This chapter presents the results and discussion of the developed system, SpeakReady AI. "
        "It provides the presentation of the system, the progressive web application implementation "
        "for desktop and mobile use, the completed features, the respondent profile, the survey and "
        "evaluation results, the testing results, and the interpretation of findings based on real "
        "evidence gathered during system evaluation.",
    )

    add_heading(doc, "4 1 Introduction", 2)
    add_body_paragraph(
        doc,
        "SpeakReady AI was developed as an AI assisted interview preparation system that helps users "
        "practice job interview questions, submit text or voice based answers, receive formative "
        "feedback, monitor progress, and access learning activities. The system also includes an "
        "administrator side for managing users, interview questions, learning modules, game levels, "
        "sessions, feedback audits, notifications, and AI provider configuration.",
    )
    add_body_paragraph(
        doc,
        "The presentation in this chapter should be supported by screenshots, testing records, and "
        "survey responses from actual respondents. All survey tables in this format are intentionally "
        "left editable so the researchers can encode the real ratings gathered from students, job "
        "seekers, HR or career evaluators, IT experts, and admin or faculty testers.",
    )

    add_heading(doc, "4 2 Presentation of the Developed System", 2)
    add_body_paragraph(
        doc,
        "The developed system provides separate desktop and mobile interfaces. The desktop view is "
        "suitable for wider dashboards, reports, and administrative management, while the mobile view "
        "supports quick access to interview preparation features using smartphones. The following "
        "figures must be replaced with actual screenshots from the final deployed or locally tested "
        "system.",
    )

    add_caption(doc, "Table 4 1 Recommended Desktop Screenshots")
    add_table(
        doc,
        ["Figure", "Screenshot to Insert", "Purpose in Chapter 4"],
        [
            ["Figure 4 1", "Desktop landing page", "Shows the public entry point and system identity."],
            ["Figure 4 2", "Desktop login or registration page", "Shows user access and authentication."],
            ["Figure 4 3", "Desktop user dashboard", "Shows readiness summary, interview history, and quick actions."],
            ["Figure 4 4", "Desktop interview setup page", "Shows selection of category, difficulty, and interview options."],
            ["Figure 4 5", "Desktop live interview session", "Shows the actual mock interview workspace."],
            ["Figure 4 6", "Desktop interview review page", "Shows answers, scores, and coaching output."],
            ["Figure 4 7", "Desktop feedback center", "Shows feedback review and answer improvement guidance."],
            ["Figure 4 8", "Desktop reports page", "Shows performance summary and progress evidence."],
            ["Figure 4 9", "Desktop AI coach page", "Shows chat based interview coaching."],
            ["Figure 4 10", "Desktop learning modules page", "Shows learning resources and modules."],
            ["Figure 4 11", "Desktop learning games page", "Shows challenge based interview practice."],
            ["Figure 4 12", "Desktop admin dashboard", "Shows administrative metrics and management overview."],
            ["Figure 4 13", "Desktop admin users page", "Shows account management and user monitoring."],
            ["Figure 4 14", "Desktop admin questions page", "Shows interview question management."],
            ["Figure 4 15", "Desktop admin modules page", "Shows learning module management."],
            ["Figure 4 16", "Desktop admin AI providers page", "Shows AI provider configuration and fallback controls."],
        ],
        [Inches(1.05), Inches(2.35), Inches(3.1)],
    )

    add_caption(doc, "Table 4 2 Recommended Mobile Screenshots")
    add_table(
        doc,
        ["Figure", "Screenshot to Insert", "Purpose in Chapter 4"],
        [
            ["Figure 4 17", "Mobile landing page", "Shows the responsive public page."],
            ["Figure 4 18", "Mobile login or registration page", "Shows mobile authentication."],
            ["Figure 4 19", "Mobile dashboard", "Shows the mobile user home screen."],
            ["Figure 4 20", "Mobile interview setup page", "Shows mobile preparation workflow."],
            ["Figure 4 21", "Mobile live interview session", "Shows mobile answer submission."],
            ["Figure 4 22", "Mobile feedback page", "Shows feedback on a small screen."],
            ["Figure 4 23", "Mobile progress page", "Shows progress tracking in mobile format."],
            ["Figure 4 24", "Mobile AI coach page", "Shows coaching conversation access."],
            ["Figure 4 25", "Mobile notifications page", "Shows alerts and activity records."],
            ["Figure 4 26", "Mobile account page", "Shows profile and account settings."],
        ],
        [Inches(1.05), Inches(2.35), Inches(3.1)],
    )

    add_caption(doc, "Figure 4 1 Desktop Landing Page")
    add_placeholder_line(doc, "actual desktop landing page screenshot")
    add_body_paragraph(
        doc,
        "Figure 4 1 shows the desktop landing page of SpeakReady AI. This page introduces the system "
        "and provides access to registration, login, and system information for users who want to "
        "prepare for interviews.",
    )

    add_caption(doc, "Figure 4 2 Mobile Dashboard")
    add_placeholder_line(doc, "actual mobile dashboard screenshot")
    add_body_paragraph(
        doc,
        "Figure 4 2 shows the mobile dashboard of SpeakReady AI. The layout is adjusted for smaller "
        "screens so users can access interview practice, feedback, progress, learning modules, and "
        "notifications using a mobile device.",
    )

    add_heading(doc, "4 3 PWA Desktop and Mobile Implementation", 2)
    add_body_paragraph(
        doc,
        "SpeakReady AI was implemented as a progressive web application. The system can be opened "
        "through a browser and may be installed as an app shortcut on supported desktop and mobile "
        "devices. This implementation supports users who need access to interview preparation tools "
        "without requiring installation from an app store.",
    )

    add_caption(doc, "Table 4 3 PWA Desktop and Mobile Implementation Evidence")
    add_table(
        doc,
        ["PWA Item", "Evidence to Provide", "Result"],
        [
            ["Responsive desktop layout", "Screenshot of desktop dashboard or report page", "To be filled after testing"],
            ["Responsive mobile layout", "Screenshot of mobile dashboard or interview page", "To be filled after testing"],
            ["Desktop PWA install", "Screenshot of browser install prompt or installed desktop shortcut", "To be filled after testing"],
            ["Mobile PWA install", "Screenshot of add to home screen or installed mobile app icon", "To be filled after testing"],
            ["Manifest file", "Screenshot or source reference to manifest configuration", "To be filled after testing"],
            ["Service worker", "Screenshot or source reference to service worker registration", "To be filled after testing"],
            ["Remembered login or session access", "Test record showing retained access after reopening", "To be filled after testing"],
        ],
        [Inches(1.85), Inches(2.95), Inches(1.55)],
    )

    add_caption(doc, "Figure 4 3 Desktop PWA Installation Evidence")
    add_placeholder_line(doc, "desktop PWA install prompt or installed shortcut screenshot")
    add_caption(doc, "Figure 4 4 Mobile PWA Installation Evidence")
    add_placeholder_line(doc, "mobile add to home screen or installed app screenshot")

    add_heading(doc, "4 4 System Features and Functionalities", 2)
    add_body_paragraph(
        doc,
        "The major features of SpeakReady AI are grouped into user side functions, administrator "
        "functions, and AI assisted features. These functions were selected because they directly "
        "support the project objective of providing accessible interview preparation, feedback, and "
        "progress monitoring.",
    )

    add_caption(doc, "Table 4 4 User Side Functionalities")
    add_table(
        doc,
        ["Functionality", "Description", "Evidence to Attach"],
        [
            ["Registration and login", "Allows users to create and access accounts using supported authentication methods.", "Login or registration screenshot"],
            ["Interview setup", "Allows users to choose category, difficulty, position, and answer mode before practice.", "Interview setup screenshot"],
            ["Mock interview session", "Presents interview questions and collects answers during practice.", "Live interview screenshot"],
            ["Text and voice answers", "Supports typed answers and voice based answer submission where configured.", "Answer submission screenshot"],
            ["AI feedback", "Provides formative feedback, score indicators, and coaching suggestions.", "Feedback or review screenshot"],
            ["AI coach", "Provides interview related guidance through a coaching conversation.", "AI coach screenshot"],
            ["Progress tracking", "Shows interview history, score trends, readiness indicators, and practice activity.", "Progress screenshot"],
            ["Reports", "Summarizes user performance and areas for improvement.", "Reports screenshot"],
            ["Learning modules", "Provides learning materials for interview preparation.", "Modules screenshot"],
            ["Learning games", "Provides challenge based practice and certificates.", "Game screenshot"],
            ["Notifications", "Displays user alerts, announcements, and activity updates.", "Notifications screenshot"],
            ["Account management", "Allows profile, password, language, and account updates.", "Account screenshot"],
        ],
        [Inches(1.65), Inches(3.25), Inches(1.8)],
    )

    add_caption(doc, "Table 4 5 Administrator Functionalities")
    add_table(
        doc,
        ["Functionality", "Description", "Evidence to Attach"],
        [
            ["Dashboard monitoring", "Displays user, session, readiness, and activity indicators.", "Admin dashboard screenshot"],
            ["User management", "Allows admins to view, create, update, export, and manage user status.", "Admin users screenshot"],
            ["Category management", "Allows admins to manage interview and learning game categories.", "Admin categories screenshot"],
            ["Question management", "Allows CRUD, bulk deletion, import, export, analytics, and AI generation of questions.", "Admin questions screenshot"],
            ["Module management", "Allows creation, editing, AI filling, and chapter management for learning modules.", "Admin modules screenshot"],
            ["Learning game management", "Allows admins to create or generate game levels and manage challenge details.", "Admin game screenshot"],
            ["Session monitoring", "Allows admins to review, flag, archive, restore, delete, and export interview sessions.", "Admin sessions screenshot"],
            ["Feedback audit", "Allows verification and review of feedback records and notes.", "Admin feedback screenshot"],
            ["AI provider management", "Allows provider configuration, primary provider selection, fallback control, and evaluation.", "Admin AI screenshot"],
            ["System settings", "Allows system configuration, backup, restore, and maintenance controls.", "Admin settings screenshot"],
            ["Notifications", "Allows admins to send announcements and manage notifications.", "Admin notifications screenshot"],
        ],
        [Inches(1.65), Inches(3.25), Inches(1.8)],
    )

    add_caption(doc, "Table 4 6 AI and Algorithm Components")
    add_table(
        doc,
        ["Component", "Purpose", "Chapter 4 Evidence"],
        [
            ["AI question generation", "Generates or adapts interview questions based on category, role, and difficulty.", "Generated question sample and admin page screenshot"],
            ["Question recommendation", "Uses source backed retrieval and fallback selection to choose relevant practice questions.", "Interview setup and question evidence"],
            ["Speech transcription", "Converts spoken responses into text when configured.", "Voice answer test record"],
            ["Local speech assessment", "Provides optional pronunciation and delivery evidence.", "Speech assessment test record"],
            ["Evidence based coaching", "Creates feedback based on answer content and available scoring evidence.", "Review page screenshot"],
            ["Weighted readiness scoring", "Combines score indicators to summarize interview readiness.", "Report or score screenshot"],
            ["KNN readiness matching", "Compares scored sessions with similar historical records as a secondary readiness signal.", "Admin or testing evidence"],
            ["AI provider fallback", "Keeps the system usable when an external provider is unavailable.", "Provider evaluation or fallback test record"],
        ],
        [Inches(1.75), Inches(3.05), Inches(1.55)],
    )

    add_heading(doc, "4 5 Respondents of the Study", 2)
    add_body_paragraph(
        doc,
        "The respondents of the study should be selected based on their relationship to the system. "
        "Students and job seekers evaluate the usefulness and usability of the interview preparation "
        "features. HR or career evaluators review the relevance of interview questions and feedback. "
        "IT experts evaluate technical qualities such as functionality, reliability, performance, "
        "security, and maintainability. Admin or faculty testers evaluate the administrative side of "
        "the system.",
    )

    add_caption(doc, "Table 4 7 Recommended Respondent Composition")
    add_table(
        doc,
        ["Respondent Group", "Evaluation Focus", "Suggested Target", "Actual Number"],
        [
            ["Students or job seekers", "Usefulness, usability, interview preparation experience, mobile and desktop access", "30", "_____"],
            ["HR or career evaluators", "Question relevance, realism of feedback, interview preparation value", "3", "_____"],
            ["IT experts", "Functionality, reliability, security, performance, usability, maintainability", "3", "_____"],
            ["Admin or faculty testers", "Admin dashboard, user management, question management, reports, monitoring", "2", "_____"],
            ["Total", "Combined respondent count", "38", "_____"],
        ],
        [Inches(1.65), Inches(3.0), Inches(1.0), Inches(1.0)],
    )

    add_caption(doc, "Table 4 8 Respondent Profile Summary")
    add_table(
        doc,
        ["Profile Variable", "Category", "Frequency", "Percentage"],
        [
            ["Respondent type", "Student or job seeker", "_____", "_____"],
            ["Respondent type", "HR or career evaluator", "_____", "_____"],
            ["Respondent type", "IT expert", "_____", "_____"],
            ["Respondent type", "Admin or faculty tester", "_____", "_____"],
            ["Device used for testing", "Desktop or laptop", "_____", "_____"],
            ["Device used for testing", "Mobile phone", "_____", "_____"],
            ["Device used for testing", "Both desktop and mobile", "_____", "_____"],
        ],
        [Inches(1.6), Inches(2.35), Inches(1.15), Inches(1.15)],
    )

    add_heading(doc, "4 6 Survey and Evaluation Results", 2)
    add_body_paragraph(
        doc,
        "A five point Likert scale may be used to measure the respondents' evaluation of the system. "
        "The weighted mean must be computed using actual survey responses. The researchers should "
        "attach screenshots of the Google Form summary, exported spreadsheet, or signed printed forms "
        "as supporting evidence.",
    )

    add_caption(doc, "Table 4 9 Likert Scale and Interpretation")
    add_table(
        doc,
        ["Scale", "Verbal Interpretation", "Weighted Mean Range"],
        [
            ["5", "Excellent or Strongly Agree", "4.50 to 5.00"],
            ["4", "Very Good or Agree", "3.50 to 4.49"],
            ["3", "Good or Neutral", "2.50 to 3.49"],
            ["2", "Fair or Disagree", "1.50 to 2.49"],
            ["1", "Poor or Strongly Disagree", "1.00 to 1.49"],
        ],
        [Inches(0.85), Inches(3.1), Inches(2.0)],
    )

    add_caption(doc, "Table 4 10 User Acceptance Evaluation Results")
    add_table(
        doc,
        ["Evaluation Criteria", "Total Score", "Number of Respondents", "Weighted Mean", "Interpretation"],
        [
            ["Functionality", "_____", "_____", "_____", "_____"],
            ["Usability", "_____", "_____", "_____", "_____"],
            ["Reliability", "_____", "_____", "_____", "_____"],
            ["Performance efficiency", "_____", "_____", "_____", "_____"],
            ["Security", "_____", "_____", "_____", "_____"],
            ["Desktop responsiveness", "_____", "_____", "_____", "_____"],
            ["Mobile responsiveness", "_____", "_____", "_____", "_____"],
            ["PWA accessibility and installability", "_____", "_____", "_____", "_____"],
            ["Accuracy and usefulness of AI feedback", "_____", "_____", "_____", "_____"],
            ["Usefulness for interview preparation", "_____", "_____", "_____", "_____"],
            ["Overall acceptability", "_____", "_____", "_____", "_____"],
        ],
        [Inches(2.1), Inches(0.9), Inches(1.1), Inches(0.9), Inches(1.15)],
    )

    add_body_paragraph(
        doc,
        "The weighted mean may be computed by dividing the total score by the number of respondents. "
        "For example, Weighted Mean equals Total Score divided by Number of Respondents. The "
        "interpretation must follow the scale shown in Table 4 9.",
    )

    add_caption(doc, "Table 4 11 Expert Evaluation Results")
    add_table(
        doc,
        ["Expert Group", "Criteria Evaluated", "Weighted Mean", "Interpretation", "Remarks"],
        [
            ["IT experts", "Functionality", "_____", "_____", "_____"],
            ["IT experts", "Reliability", "_____", "_____", "_____"],
            ["IT experts", "Performance efficiency", "_____", "_____", "_____"],
            ["IT experts", "Security", "_____", "_____", "_____"],
            ["IT experts", "Maintainability", "_____", "_____", "_____"],
            ["HR or career evaluators", "Question relevance", "_____", "_____", "_____"],
            ["HR or career evaluators", "Feedback usefulness", "_____", "_____", "_____"],
            ["Admin or faculty testers", "Admin usability", "_____", "_____", "_____"],
        ],
        [Inches(1.35), Inches(1.75), Inches(0.85), Inches(1.0), Inches(1.35)],
    )

    add_heading(doc, "4 7 System Testing Results", 2)
    add_body_paragraph(
        doc,
        "System testing was conducted to verify whether the completed functions of SpeakReady AI "
        "performed as expected. The testing records should include both functional testing and PWA "
        "desktop and mobile testing.",
    )

    add_caption(doc, "Table 4 12 Functional Testing Results")
    add_table(
        doc,
        ["Test Case", "Expected Result", "Actual Result", "Status"],
        [
            ["User registration", "User can create an account successfully.", "_____", "_____"],
            ["User login", "User can log in using valid credentials.", "_____", "_____"],
            ["Google authentication", "User can sign in using Google when configured.", "_____", "_____"],
            ["Terms acceptance", "User must accept terms before accessing protected pages.", "_____", "_____"],
            ["Interview setup", "User can select interview category, difficulty, and options.", "_____", "_____"],
            ["Start interview session", "System creates a new interview session.", "_____", "_____"],
            ["Submit text answer", "System saves typed answer and proceeds correctly.", "_____", "_____"],
            ["Submit voice answer", "System records or transcribes voice answer when supported.", "_____", "_____"],
            ["Generate feedback", "System displays answer feedback and score information.", "_____", "_____"],
            ["Review session", "User can view completed session results.", "_____", "_____"],
            ["Progress tracking", "System displays score trends and practice activity.", "_____", "_____"],
            ["Reports", "System displays interview report summary.", "_____", "_____"],
            ["AI coach", "System responds to interview related coaching questions.", "_____", "_____"],
            ["Learning modules", "User can view modules and update progress.", "_____", "_____"],
            ["Learning games", "User can start and complete learning game challenges.", "_____", "_____"],
            ["Notifications", "User can view, read, and clear notifications.", "_____", "_____"],
            ["Admin user management", "Admin can manage user records.", "_____", "_____"],
            ["Admin question management", "Admin can add, edit, delete, import, export, and generate questions.", "_____", "_____"],
            ["Admin session monitoring", "Admin can review, archive, restore, and export sessions.", "_____", "_____"],
            ["Admin AI provider management", "Admin can configure providers and fallback settings.", "_____", "_____"],
        ],
        [Inches(1.55), Inches(2.15), Inches(1.85), Inches(0.7)],
    )

    add_caption(doc, "Table 4 13 PWA Desktop and Mobile Testing Results")
    add_table(
        doc,
        ["Test Case", "Expected Result", "Actual Result", "Status"],
        [
            ["Desktop browser access", "System opens properly in a desktop browser.", "_____", "_____"],
            ["Mobile browser access", "System opens properly in a mobile browser.", "_____", "_____"],
            ["Desktop responsive layout", "Desktop pages display without broken layout or overlapping text.", "_____", "_____"],
            ["Mobile responsive layout", "Mobile pages adjust correctly to the phone screen.", "_____", "_____"],
            ["Desktop PWA install", "System can be installed or added as an app shortcut on desktop.", "_____", "_____"],
            ["Mobile PWA install", "System can be added to the mobile home screen.", "_____", "_____"],
            ["App icon and name", "Installed PWA displays the correct icon and app name.", "_____", "_____"],
            ["Offline or cached shell behavior", "Service worker loads supported cached resources where applicable.", "_____", "_____"],
            ["Reopen installed app", "Installed PWA opens the system without layout problems.", "_____", "_____"],
            ["Remembered login behavior", "User session behaves according to the system login policy after reopening.", "_____", "_____"],
        ],
        [Inches(1.55), Inches(2.25), Inches(1.85), Inches(0.7)],
    )

    add_heading(doc, "4 8 Discussion of Findings", 2)
    add_body_paragraph(
        doc,
        "After the survey responses are encoded, this section should explain what the results mean. "
        "If the overall acceptability rating falls within the Excellent or Very Good range, the "
        "discussion may state that the respondents generally accepted SpeakReady AI as a useful "
        "interview preparation system. The discussion must mention the actual highest rated and "
        "lowest rated criteria from Table 4 10.",
    )
    add_body_paragraph(
        doc,
        "The findings should also discuss the difference between desktop and mobile use. Desktop "
        "access is expected to support wider dashboards, reports, and administrator workflows. Mobile "
        "access is expected to support convenient practice, review, coaching, notifications, and "
        "progress monitoring. The PWA evidence should show whether users can install or add the system "
        "as an app shortcut on supported devices.",
    )
    add_body_paragraph(
        doc,
        "The comments and suggestions from respondents should be summarized without changing their "
        "meaning. Common suggestions may be grouped by theme, such as interface improvement, feedback "
        "clarity, mobile layout, speed, speech transcription, interview question coverage, or admin "
        "controls. Each issue should be connected to a possible improvement for the system.",
    )

    add_caption(doc, "Table 4 14 Summary of Respondent Comments and Suggestions")
    add_table(
        doc,
        ["Theme", "Common Comment or Suggestion", "Possible Action"],
        [
            ["User interface", "_____", "_____"],
            ["Mobile experience", "_____", "_____"],
            ["AI feedback", "_____", "_____"],
            ["Speech or voice feature", "_____", "_____"],
            ["Learning modules or games", "_____", "_____"],
            ["Admin management", "_____", "_____"],
            ["Performance or loading speed", "_____", "_____"],
            ["Security or privacy", "_____", "_____"],
        ],
        [Inches(1.45), Inches(3.0), Inches(2.1)],
    )

    add_heading(doc, "4 9 Summary", 2)
    add_body_paragraph(
        doc,
        "This chapter presented the developed SpeakReady AI system, including its desktop and mobile "
        "PWA implementation, system features, respondent profile, survey results, testing results, "
        "and discussion of findings. The completed Chapter 4 should show that the system was evaluated "
        "using actual respondents and real evidence such as screenshots, survey summaries, testing "
        "records, and expert validation forms.",
    )
    add_body_paragraph(
        doc,
        "Based on the final encoded survey results, the researchers should state the overall level of "
        "acceptability and identify the system areas that were strongest and the areas that still need "
        "improvement. The final version of this chapter must be revised using the actual weighted "
        "means, respondent counts, screenshots, and evaluation comments gathered during testing.",
    )

    add_heading(doc, "Evidence Checklist for Chapter 4", 2)
    add_bullets(
        doc,
        [
            "Desktop screenshots of the main user and admin pages.",
            "Mobile screenshots of the main user pages.",
            "PWA installation evidence for desktop and mobile devices.",
            "Google Form or printed survey responses from actual respondents.",
            "Survey spreadsheet or encoded summary of ratings.",
            "Signed validation or evaluation forms from IT experts and HR or career evaluators.",
            "Functional testing table with actual results.",
            "PWA testing table with actual results.",
            "Respondent comments and suggestions.",
            "Photos during system evaluation if allowed by the school and respondents.",
        ],
    )

    doc.add_section(WD_SECTION.NEW_PAGE)
    add_heading(doc, "Sample Survey Questionnaire", 1)
    add_body_paragraph(
        doc,
        "The following questionnaire may be used as the basis for the Chapter 4 evaluation. The "
        "researchers may convert this into a Google Form or printed evaluation sheet.",
    )
    add_caption(doc, "Table 4 15 User Survey Questionnaire")
    add_table(
        doc,
        ["Criteria", "Statement", "5", "4", "3", "2", "1"],
        [
            ["Functionality", "The system provides the needed features for interview preparation.", "", "", "", "", ""],
            ["Usability", "The system is easy to understand and use.", "", "", "", "", ""],
            ["Reliability", "The system performs its functions consistently during testing.", "", "", "", "", ""],
            ["Performance", "The system loads and responds within an acceptable time.", "", "", "", "", ""],
            ["Security", "The system protects user access and private interview records.", "", "", "", "", ""],
            ["Desktop responsiveness", "The desktop layout is clear and easy to navigate.", "", "", "", "", ""],
            ["Mobile responsiveness", "The mobile layout is clear and easy to navigate.", "", "", "", "", ""],
            ["PWA access", "The system is convenient to access as a PWA on supported devices.", "", "", "", "", ""],
            ["AI feedback", "The AI feedback is useful for improving interview answers.", "", "", "", "", ""],
            ["Interview preparation value", "The system helps users prepare for real interviews.", "", "", "", "", ""],
            ["Overall acceptability", "Overall, the system is acceptable for interview preparation.", "", "", "", "", ""],
        ],
        [Inches(1.1), Inches(3.3), Inches(0.32), Inches(0.32), Inches(0.32), Inches(0.32), Inches(0.32)],
    )

    add_body_paragraph(
        doc,
        "Open ended questions may be added after the rating items. Suggested questions are: What "
        "feature of SpeakReady AI is most useful for interview preparation? What part of the system "
        "needs improvement? Would you recommend this system to students or job seekers? Why?",
    )

    OUTPUT.parent.mkdir(parents=True, exist_ok=True)
    doc.save(OUTPUT)
    print(str(OUTPUT))


if __name__ == "__main__":
    build_document()
