// @ts-check
const { test, expect } = require("@playwright/test");

test.describe("Mixtape functionality", () => {
  test.beforeEach(async ({ page }) => {
    await page.goto("http://test.local/sample-page/");
  });

  test("should show report button when text is selected", async ({ page }) => {
    await page.getByText("This is an example page. It’s").hover();
    await page.mouse.down();
    await page.getByText("The XYZ Doohickey Company was").hover();
    await page.mouse.up();

    const reportButton = await page.locator(".mixtape-report-button");
    await expect(reportButton).toBeVisible();
  });

  test("should hide report button when text is deselected", async ({
    page,
  }) => {
    await page.getByText("This is an example page. It’s").hover();
    await page.mouse.down();
    await page.getByText("The XYZ Doohickey Company was").hover();
    await page.mouse.up();

    await page.evaluate(() => {
      const selection = window.getSelection();
      if (selection) {
        selection.removeAllRanges();
      }
      document.dispatchEvent(new Event("selectionchange"));
    });

    const reportButton = await page.locator(".mixtape-report-button");
    await expect(reportButton).not.toBeVisible();
  });

  test("should show dialog when Ctrl+Enter is pressed", async ({ page }) => {
    await page.getByText("This is an example page. It’s").hover();
    await page.mouse.down();
    await page.getByText("The XYZ Doohickey Company was").hover();
    await page.mouse.up();

    await page.keyboard.press("Control+Enter");

    const dialog = await page.locator("#mixtape_dialog");
    await expect(dialog).toBeVisible();
  });

  test("should send report when report button is clicked", async ({ page }) => {
    await page.getByText("This is an example page. It’s").hover();
    await page.mouse.down();
    await page.getByText("The XYZ Doohickey Company was").hover();
    await page.mouse.up();

    await page.locator(".mixtape-report-button").click();

    const dialog = await page.locator("#mixtape_dialog");
    await expect(dialog).toBeVisible();

    await page.locator(".mixtape_action[data-action=send]").click();

    await expect(dialog).not.toBeVisible();
  });
});
