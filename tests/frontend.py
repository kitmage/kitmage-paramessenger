"""Run against a WordPress site with this plugin active: python3 tests/frontend.py."""

import os
import unittest
from urllib.parse import urlencode
from urllib.request import urlopen


class FrontendMessages(unittest.TestCase):
    def page(self, parameters):
        base = os.environ.get("WORDPRESS_TEST_URL", "http://127.0.0.1:8080/")
        with urlopen(base + "?" + urlencode(parameters), timeout=15) as response:
            self.assertEqual(response.status, 200)
            return response.read().decode()

    def test_purchase_agreement_message(self):
        message = (
            "Thank you for signing the Terms of Purchase Agreement. "
            "Please select your training to resume checkout."
        )
        page = self.page({"kmpm": message, "kmpc": "purchase-agreement"})
        expected = (
            '<div class="kitmage-paramessenger purchase-agreement" '
            'role="status">' + message + "</div>"
        )
        self.assertEqual(page.count(expected), 1)

    def test_safe_html_and_classes(self):
        page = self.page({"kmpm": "<strong>Hello</strong>", "kmpc": "foo notice"})
        self.assertIn(
            '<div class="kitmage-paramessenger foo notice" role="status">'
            '<strong>Hello</strong></div>', page
        )

    def test_unsafe_html_and_attributes(self):
        page = self.page({
            "kmpm": '<img src=x onerror="alert(1)"><a href="javascript:alert(2)" '
                    'onclick="alert(3)">safe link</a>',
            "kmpc": 'notice" onclick="alert(4)',
        })
        self.assertIn('role="status"><a href="alert(2)">safe link</a></div>', page)
        self.assertNotIn('onerror="alert(1)"', page)
        self.assertNotIn('javascript:alert(2)', page)
        self.assertNotIn('onclick="alert(3)"', page)
        self.assertNotIn('onclick="alert(4)', page)

    def test_missing_empty_and_array_messages(self):
        for parameters in ({}, {"kmpm": ""}, {"kmpm[]": "hello"}):
            with self.subTest(parameters=parameters):
                self.assertNotIn('class="kitmage-paramessenger', self.page(parameters))

    def test_array_classes_and_literal_encoding(self):
        page = self.page({"kmpm": "It's 100% + %3Cstrong%3E", "kmpc[]": "foo"})
        self.assertIn(
            'class="kitmage-paramessenger" role="status">'
            "It's 100% + %3Cstrong%3E</div>", page
        )


if __name__ == "__main__":
    unittest.main()
