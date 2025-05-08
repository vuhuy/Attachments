import os
import pytest
from pathlib import Path
from selenium import webdriver
from selenium.webdriver.chrome.options import Options as ChromeOptions
# from selenium.webdriver.firefox.options import Options as FirefoxOptions


def pytest_addoption(parser):
    parser.addini("base_url", "MediaWiki base url", default="http://localhost:8080")

@pytest.fixture(params=["chrome"])
def browser(request):
    if request.param == "chrome":
        options = ChromeOptions()
        options.add_argument("--headless")
        options.add_argument("--no-sandbox")
        options.add_argument("--disable-dev-shm-usage")
        driver = webdriver.Chrome(options=options)
    # elif request.param == "firefox":
    #     options = FirefoxOptions()

    #     # Workaround for Firefox snap on Ubuntu systems. 
    #     # The test will run, but you will get errors when it tries to terminate Firefox.
    #     snap_firefox_path = Path("/snap/firefox/current/usr/lib/firefox/firefox")
    #     if snap_firefox_path.exists():
    #         options.binary_location = str(snap_firefox_path)

    #     options.add_argument("--headless")
    #     driver = webdriver.Firefox(options=options)
    else:
        raise ValueError(f"Unsupported browser: {request.param}")

    driver.set_window_size(1920, 1080)
    yield driver
    driver.quit()

@pytest.fixture(scope="session")
def base_url(request):
    return request.config.getini("base_url")

@pytest.fixture(scope='session', autouse=True)
def clean_screenshots():
    folder = "tests/screenshots"
    os.makedirs(folder, exist_ok=True)

    for file in os.listdir(folder):
        path = os.path.join(folder, file)

        if os.path.isfile(path):
            os.remove(path)
