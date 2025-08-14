import { test } from "./../../test";
import { turnOffBinary } from "../../../helpers";
import { expect } from "@playwright/test";
import { choproRedirectGuestUser } from "../../../flows/checkoutpro/pay_with_creditcard";

test.beforeAll(async () => {
    await turnOffBinary();
});

test('test successful payment with chopro redirect, payment must be approved and success page must be shown', async ({ page, siteIdParams }) => {
    const returnButton = page.locator('#group_button_back_congrats');
    const congratsApproved = page.locator('.congrats--approved');

    await choproRedirectGuestUser(page, siteIdParams.credit_cards.master, siteIdParams.user.document, "APRO");

    await expect(congratsApproved).toBeVisible();
    await expect(returnButton).toBeVisible();
    returnButton.click();

    await page.waitForLoadState();
    await page.waitForSelector('.checkout-onepage-success');
})

test('test rejected payment with chopro redirect, change payment method, other payment options must be shown', async ({ page, siteIdParams }) => {
    const changePaymentMethod = page.locator('#change_payment_method');
    const otherPaymentOptions = page.locator('.payment-option-desktop-screen__content');

    await choproRedirectGuestUser(page, siteIdParams.credit_cards.master, siteIdParams.user.document, "OTHE");

    await expect(changePaymentMethod).toBeVisible();
    await changePaymentMethod.click();

    await page.waitForLoadState();
    await expect(otherPaymentOptions).toBeVisible();
})

test('test cancelled payment with chopro redirect after reject payment', async ({ page, siteIdParams }) => {
    const congratsRejected = page.locator('.congrats--rejected');
    const changePaymentMethod = page.locator('#change_payment_method');
    const cancelPayment = page.locator('.group-back-url  .andes-button__content');

    await choproRedirectGuestUser(page, siteIdParams.credit_cards.master, siteIdParams.user.document, "OTHE");

    await expect(congratsRejected).toBeVisible();
    await expect(changePaymentMethod).toBeVisible();
    await cancelPayment.click();

    await page.waitForLoadState();
    await expect(page.locator('#maincontent')).toBeVisible();
})

test('test pending payment with chopro modal, binary must be off, payment must be approved and success page must be shown', async ({ page, siteIdParams }) => {
    const returnButton = page.locator('#button');
    const congratsPending = page.locator('.congrats--recover');
    const checkoutSuccess = page.locator('.checkout-success');

    await choproRedirectGuestUser(page, siteIdParams.credit_cards.master, siteIdParams.user.document, "CONT");

    await expect(congratsPending).toBeVisible();
    await expect(returnButton).toBeVisible();
    returnButton.click();

    await page.waitForLoadState();
    await expect(checkoutSuccess).toBeVisible();
})
