import { payWithOneCard } from "../../../flows/checkoutcustom/pay_with_one_card";
import { expect } from "@playwright/test";

export async function approvedTest(page, siteIdParams, card, status) {
    await payWithOneCard(page, card, siteIdParams.user.document, status);

    await page.waitForSelector('.checkout-onepage-success');

    await expect(page.locator('.checkout-success')).toBeVisible();
}

export async function rejectedTest(page, siteIdParams, card, status) {
    await payWithOneCard(page, card, siteIdParams.user.document, status);

    await page.waitForSelector('.message.message-error.error');

    await expect(page.locator('.message.message-error.error')).toBeVisible();
}
