import { test } from "./../../test";
import { expect } from "@playwright/test";
import { payWithTwoCards } from "../../../flows/checkoutcustom/pay_with_two_cards";

test('test credit successful payment with two cards, payment must be approved and success page must be shown', async ({page, siteIdParams}) => {
    await payWithTwoCards(page, siteIdParams.credit_cards.master, siteIdParams.credit_cards.visa, "APRO", "APRO", siteIdParams.user.document);

    await page.waitForLoadState();
    await page.waitForTimeout(5000);
    await expect(page.locator('.checkout-onepage-success')).toBeVisible();
});

test('test reject payment with first card, payment must be rejected and error page must be shown', async ({page, siteIdParams}) => {
    await payWithTwoCards(page, siteIdParams.credit_cards.master, siteIdParams.credit_cards.visa, "OTHE", "APRO", siteIdParams.user.document);

    await page.waitForLoadState();
    await page.waitForTimeout(2000);
    await expect(page.locator('.message.message-error.error')).toBeVisible();
});

test('test reject payment with second card, payment must be rejected and error page must be shown', async ({page, siteIdParams}) => {
    await payWithTwoCards(page, siteIdParams.credit_cards.master, siteIdParams.credit_cards.visa, "APRO", "OTHE", siteIdParams.user.document);

    await page.waitForLoadState();
    await page.waitForTimeout(2000);
    await expect(page.locator('.message.message-error.error')).toBeVisible();
});

test('test pending payment with two cards, payment must be rejected and error page must be shown', async ({page, siteIdParams}) => {
    await payWithTwoCards(page, siteIdParams.credit_cards.master, siteIdParams.credit_cards.visa, "CONT", "CONT", siteIdParams.user.document);

    await page.waitForLoadState();
    await page.waitForTimeout(2000);
    await expect(page.locator('.message.message-error.error')).toBeVisible();
});
