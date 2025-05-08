from selenium import webdriver
from selenium.common.exceptions import TimeoutException
from selenium.webdriver.common.by import By
from selenium.webdriver.support import expected_conditions as EC
from selenium.webdriver.support.ui import WebDriverWait
import time


def assert_js_errors(browser):
    """Assert that there are no javascript errors on the current page."""

    # Todo: This is a Chrome thingy. Firefox needs DevTools.
    if isinstance(browser, webdriver.Chrome):
        time.sleep(2)
        logs = browser.get_log("browser")
        errors = [log for log in logs if log["level"] == "SEVERE"]
        assert len(errors) == 0, f"Javascript errors detected: {errors}."

def assert_php_errors(browser):
    """Assert that there are no php errors on the current page."""

    # Not ideal, but does the job.
    src = browser.page_source.strip().lower()
    assert "parse error: " not in src and "<b>parse error</b>: " not in src, f"PHP parse error detected."
    assert "warning: " not in src and "<b>warning</b>: " not in src, f"PHP warning detected."
    assert "fatal error: " not in src and "<b>fatal error</b>: " not in src, f"PHP fatal error detected."
    assert "error: " not in src and "<b>error</b>: " not in src, f"PHP error detected."
    assert "deprecated: " not in src and "<b>deprecated</b>: " not in src, f"PHP deprectation warning detected."

def save_screenshot(browser, name):
    """Save a screenshot of the current page."""

    if isinstance(browser, webdriver.Chrome):
        browser.save_screenshot(f'tests/screenshots/Chrome-{name}.png')
    elif isinstance(browser, webdriver.Firefox):
        browser.save_screenshot(f'tests/screenshots/Firefox-{name}.png')
    else:
        browser.save_screenshot(f'tests/screenshots/Browser-{name}.png')
