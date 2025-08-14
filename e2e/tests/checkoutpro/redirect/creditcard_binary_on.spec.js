import { test } from "./../../test";
import { turnOnBinary } from "../../../helpers";
import { expect } from "@playwright/test";
import { choproRedirectGuestUser } from "../../../flows/checkoutpro/pay_with_creditcard";

test.beforeAll(async () => {
    await turnOnBinary();
});

test('test pending payment with chopro redirect, change payment method, other payment options must be shown', async ({ page, siteIdParams }) => {
    const changePaymentMethod = page.locator('#change_payment_method');
    const otherPaymentOptions = page.locator('.payment-option-desktop-screen__content');

    await choproRedirectGuestUser(page, siteIdParams.credit_cards.master, siteIdParams.user.document, "CONT");

    await expect(changePaymentMethod).toBeVisible();
    await changePaymentMethod.click();

    await page.waitForLoadState();
    await expect(otherPaymentOptions).toBeVisible();
})

test('test cancelled payment with chopro redirect after pending payment', async ({ page, siteIdParams }) => {
    const congratsRejected = page.locator('.congrats--rejected');
    const changePaymentMethod = page.locator('#change_payment_method');
    const cancelPayment = page.locator('.group-back-url  .andes-button__content');

    await choproRedirectGuestUser(page, siteIdParams.credit_cards.master, siteIdParams.user.document, "CONT");

    await expect(congratsRejected).toBeVisible();
    await expect(changePaymentMethod).toBeVisible();
    await cancelPayment.click();

    await page.waitForLoadState();
    await expect(page.locator('#maincontent')).toBeVisible();
})
