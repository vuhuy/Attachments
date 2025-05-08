import os
import pytest
import time
from selenium.webdriver.common.by import By
from selenium.webdriver.support import expected_conditions as EC
from selenium.webdriver.support.ui import WebDriverWait
from tests.utils.helpers import assert_js_errors, assert_php_errors, save_screenshot


@pytest.mark.dependency(name="hook_control_test")
def test_hook_control(browser, base_url):
    """Javascript control test without using the extension."""

    browser.get(f"{base_url}/index.php/Control")

    try:
        assert_js_errors(browser)
        assert_php_errors(browser)

    except Exception as e:
        save_screenshot(browser, f'Hook-Control-Fail')
        raise

@pytest.mark.dependency(depends=["hook_control_test"])
def test_hook_page(browser, base_url):
    """Test hooks by adding a file, subpage and exturl."""

    try:
        browser.get(f"{base_url}/index.php?title=Attachments&action=attach")
        assert_php_errors(browser)
        save_screenshot(browser, f'Hook-Attachments-Attach')

        # Add attachment: Upload file
        current_url = browser.current_url
        browser.find_element(By.ID, "wpUploadFile").send_keys(os.path.abspath("tests/data/by.png"))
        browser.find_element(By.CSS_SELECTOR, ".mw-body-content .mw-htmlform:nth-of-type(1) .mw-htmlform-submit").click()
        WebDriverWait(browser, 5).until(EC.url_changes(current_url))

        # Add attachment: Subpage
        current_url = browser.current_url
        browser.get(f"{base_url}/index.php?title=Attachments&action=attach")
        browser.find_element(By.ID, "mw-input-wpSubpage").send_keys("My Sub Page")
        browser.find_element(By.CSS_SELECTOR, ".mw-body-content .mw-htmlform:nth-of-type(2) .mw-htmlform-submit").click()
        WebDriverWait(browser, 5).until(EC.url_changes(current_url))

        try:
            WebDriverWait(browser, 5).until(EC.visibility_of_element_located((By.CSS_SELECTOR, ".oo-ui-dialog-content .oo-ui-flaggedElement-primary .oo-ui-buttonElement-button"))).click()
            WebDriverWait(browser, 5).until_not(EC.visibility_of_element_located((By.CSS_SELECTOR, ".oo-ui-dialog")))
        except TimeoutException:
            pass

        current_url = browser.current_url
        element = WebDriverWait(browser, 5).until(EC.element_to_be_clickable((By.ID, "wpTextbox1")))
        element.send_keys("Hello sub world!")
        browser.find_element(By.ID, "wpSave").click()
        WebDriverWait(browser, 5).until(EC.url_changes(current_url))

        # Add attachment: URL
        current_url = browser.current_url
        browser.get(f"{base_url}/index.php?title=Attachments&action=attach")
        browser.find_element(By.ID, "mw-input-wpTitle").send_keys("Google")
        browser.find_element(By.ID, "mw-input-wpURL").send_keys("https://google.com")
        browser.find_element(By.CSS_SELECTOR, ".mw-body-content .mw-htmlform:nth-of-type(3) .mw-htmlform-submit").click()
        WebDriverWait(browser, 5).until(EC.url_changes(current_url))

        # Check results
        browser.find_element(By.CSS_SELECTOR, "a[title='Attachments - By.png']")
        browser.find_element(By.CSS_SELECTOR, "a[title='Attachments/My Sub Page']")
        browser.find_element(By.CSS_SELECTOR, "a[href='https://google.com']")
        browser.find_element(By.CSS_SELECTOR, ".vector-page-toolbar a[href='#mw-ext-attachments']")
        browser.find_element(By.CSS_SELECTOR, ".vector-page-toolbar a[href='/index.php?title=Attachments&action=attach']")

        save_screenshot(browser, f'Hook-Attachments-Page')

    except Exception as e:
        save_screenshot(browser, f'Hook-Attachments-Fail')
        raise
