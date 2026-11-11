"""Focused checks for local feedback wording and question coverage."""

from __future__ import annotations

import json
import subprocess
import sys
import tempfile
import unittest
from pathlib import Path


ROOT = Path(__file__).resolve().parents[2]
sys.path.insert(0, str(ROOT / "scripts"))
import predict_feedback as feedback  # noqa: E402


class LocalFeedbackTest(unittest.TestCase):
    def predict_one(self, question: str, answer: str, **extra: object) -> tuple[dict, dict]:
        model = {"models": {field: {"fallback": 90, "confidence": 1} for field in feedback.SCORE_FIELDS}}
        payload = {"answers": [{"id": 1, "question": question, "answer": answer, **extra}]}
        with tempfile.TemporaryDirectory() as directory:
            model_path = Path(directory) / "model.json"
            model_path.write_text(json.dumps(model), encoding="utf-8")
            completed = subprocess.run(
                [sys.executable, str(ROOT / "scripts" / "predict_feedback.py"), "--model", str(model_path)],
                input=json.dumps(payload), text=True, capture_output=True, check=True,
            )
        result = json.loads(completed.stdout)
        return result["per_question_feedback"][0], result["session_feedback"]

    def test_missing_criterion_is_a_specific_guide_clause(self) -> None:
        answer = {
            "question": "Tell me about a time you solved a customer issue.",
            "expected_guide": "Explain your own action and explain the final result.",
            "answer": "I checked the account and explained the delay to the customer.",
        }
        gaps = feedback.missing_criteria(answer, {"relevance_score": 70}, feedback.answer_signals(answer["answer"]))
        self.assertEqual(["explain the final result"], gaps)
        self.assertNotIn(answer["expected_guide"], gaps)

    def test_date_and_unknown_outcome_do_not_count_as_result(self) -> None:
        text = "During 2024, I checked handover notes for a team of 10. The outcome is still unknown."
        self.assertFalse(feedback.answer_signals(text)["result"])
        self.assertLess(feedback.local_star_score(text), 100)

    def test_bare_result_word_is_not_a_completed_outcome(self) -> None:
        self.assertFalse(feedback.answer_signals("I mentioned the result in my response.")["result"])
        self.assertFalse(feedback.answer_signals("I planned to improve the outcome.")["result"])
        self.assertTrue(feedback.answer_signals("The result was a 20% reduction in repeat tickets.")["result"])

    def test_negated_and_hypothetical_actions_are_not_past_work(self) -> None:
        self.assertFalse(feedback.answer_signals("I did not inspect the logs.")["personal_action"])
        self.assertFalse(feedback.answer_signals("If I reviewed the logs, I would document the cause.")["personal_action"])
        self.assertFalse(feedback.answer_signals("I never checked the account.")["personal_action"])
        self.assertTrue(feedback.answer_signals("I inspected the logs and documented the cause.")["personal_action"])

    def test_salary_feedback_asks_for_salary_detail(self) -> None:
        question = "What are your salary expectations?"
        answer = "I enjoy helping customers and working with a team."
        signals = feedback.answer_signals(answer)
        assessment = feedback.question_assessment(question, answer, "salary", signals, [])
        note = feedback.feedback_text(question, answer, "not_addressed", assessment, False)
        self.assertIn("range, amount, or clear statement of flexibility", note)
        self.assertNotIn("exact action and result", note)

    def test_possible_answer_never_appends_coaching(self) -> None:
        self.assertEqual("", feedback.fallback_answer("I checked the account and resolved the issue."))
        answer = "I checked the account. I explained the delay. The customer understood."
        self.assertEqual("", feedback.fallback_answer(answer))
        self.assertNotIn("Add the missing", feedback.fallback_answer(answer))

    def test_high_model_score_does_not_claim_unrelated_answer_is_direct(self) -> None:
        item, summary = self.predict_one(
            "What are your salary expectations?",
            "I enjoy organizing customer requests and training new teammates. I also like improving the help guide.",
        )
        self.assertLess(item["relevance_score"], 50)
        self.assertEqual("not_addressed", item["answer_alignment"])
        self.assertIn("pay expectation", item["ai_feedback"])
        self.assertNotIn("action and result", item["ai_feedback"])
        self.assertIn("range, amount, or clear statement of flexibility", summary["weaknesses"])
        self.assertNotIn("action and the result", summary["improvement_suggestions"])

    def test_behavioral_answer_without_topic_match_states_uncertainty(self) -> None:
        item, _ = self.predict_one(
            "Tell me about a time you solved a customer issue.",
            "During our shift, I inspected the logs and documented the cause. The result was a stable service.",
            question_type="Behavioral",
        )
        self.assertEqual("partially_addressed", item["answer_alignment"])
        self.assertIn("cannot confirm from these words", item["ai_feedback"])
        self.assertNotIn("addresses part of the question", item["ai_feedback"])

    def test_one_shared_topic_word_does_not_prove_direct_coverage(self) -> None:
        item, _ = self.predict_one(
            "Tell me about a time you solved a customer issue.",
            "I checked the customer account and recorded details. The result was a complete note.",
            question_type="Behavioral",
        )
        self.assertLess(item["relevance_score"], 75)
        self.assertIn('shares only "customer"', item["ai_feedback"])
        self.assertNotIn("addresses part of the question", item["ai_feedback"])

    def test_technical_answer_without_topic_match_states_uncertainty(self) -> None:
        item, _ = self.predict_one(
            "Explain how DNS works.",
            "I used a customer checklist to explain the service. I tested the guide and the result was positive.",
            question_type="Technical",
        )
        self.assertLess(item["relevance_score"], 75)
        self.assertIn("cannot confirm from these words", item["ai_feedback"])

    def test_payroll_amount_is_not_a_salary_expectation(self) -> None:
        answer = "I checked payroll records worth PHP 30,000 and corrected an error for the team."
        self.assertFalse(feedback.answer_signals(answer)["salary"])
        item, _ = self.predict_one("What are your salary expectations?", answer)
        self.assertEqual("not_addressed", item["answer_alignment"])

    def test_shift_preference_is_not_a_salary_expectation(self) -> None:
        self.assertFalse(feedback.answer_signals("I prefer flexible shifts.")["salary"])

    def test_salary_error_story_is_behavioral(self) -> None:
        question = "Tell me about a time you found a salary error and solved the issue."
        answer = "During payroll review, I checked the records and found the discrepancy. I corrected the entry and explained the fix to the team."
        self.assertEqual("behavioral", feedback.question_kind(question, {"question": question, "question_type": "Behavioral"}))
        item, _ = self.predict_one(question, answer, question_type="Behavioral")
        self.assertNotIn("pay expectation", item["ai_feedback"])

    def test_coarse_behavioral_label_does_not_apply_star_to_a_technical_explanation(self) -> None:
        question = "Describe how database indexes work."
        row = {"question": question, "question_type": "Behavioral", "expected_guide": "Use STAR when possible."}
        self.assertFalse(feedback.is_behavioral(row))
        self.assertEqual("technical", feedback.question_kind(question, row))
        style = {"question": "Describe how you work under pressure.", "question_type": "Behavioral"}
        self.assertFalse(feedback.is_behavioral(style))
        self.assertEqual("direct", feedback.question_kind(style["question"], style))

    def test_genuine_salary_expectation_can_address_the_question(self) -> None:
        answer = "I am looking for PHP 30,000 to PHP 40,000 monthly, depending on the responsibilities."
        self.assertTrue(feedback.answer_signals(answer)["salary"])
        item, _ = self.predict_one("What are your salary expectations?", answer)
        self.assertEqual("directly_addressed", item["answer_alignment"])

    def test_work_setup_summary_uses_work_setup_coaching(self) -> None:
        _, summary = self.predict_one("Can you work a hybrid schedule?", "I can work hybrid with three onsite days each week.")
        self.assertIn("availability", summary["weaknesses"])
        self.assertNotIn("action and the result", summary["improvement_suggestions"])
        self.assertIn("hybrid schedule", summary["strengths"])
        self.assertNotIn("strongest", summary["strengths"])

    def test_past_shift_reports_are_not_current_availability(self) -> None:
        answer = "I prepared shift reports for my manager but did not say when I can work."
        self.assertFalse(feedback.answer_signals(answer)["setup"])
        item, _ = self.predict_one("Can you work evening shifts?", answer)
        self.assertLess(item["relevance_score"], 75)
        self.assertIn("without a clear work arrangement", item["ai_feedback"])

    def test_stated_work_availability_counts_as_setup_answer(self) -> None:
        answer = "I can work evening shifts and weekends, and I am available for the schedule you posted."
        self.assertTrue(feedback.answer_signals(answer)["setup"])
        item, _ = self.predict_one("Can you work evening shifts?", answer)
        self.assertEqual("directly_addressed", item["answer_alignment"])

    def test_how_did_you_handle_question_is_behavioral(self) -> None:
        question = "How did you handle a difficult customer complaint?"
        prompt = {"question": question, "question_type": "Behavioral"}
        self.assertTrue(feedback.is_behavioral(prompt))
        self.assertEqual("behavioral", feedback.question_kind(question, prompt))
        self.assertFalse(feedback.is_behavioral({"question": "Explain how payroll works.", "question_type": "Technical"}))


if __name__ == "__main__":
    unittest.main()
