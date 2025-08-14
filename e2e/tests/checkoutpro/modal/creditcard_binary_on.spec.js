import { test } from "./../../test";
import { turnOnBinary } from "../../../helpers";
import { expect } from "@playwright/test";
import { choproModalGuestUser } from "../../../flows/checkoutpro/pay_with_creditcard";

test.beforeAll(async () => {
    await turnOnBinary();
});

test('test pending payment with chopro modal, change payment method, other payment options must be shown', async ({ page, siteIdParams }) => {
    const modal = page.locator('#mercadopago-checkout').contentFrame();
    const changePaymentMethod = modal.locator('#change_payment_method');
    const otherPaymentOptions = modal.locator('.payment-option-desktop-screen__content');

    await choproModalGuestUser(page, siteIdParams.credit_cards.master, siteIdParams.user.document, "CONT");

    await expect(changePaymentMethod).toBeVisible();
    await changePaymentMethod.click();

    await page.waitForLoadState();
    await expect(otherPaymentOptions).toBeVisible();
})

test('test cancelled payment with chopro modal after pending payment', async ({ page, siteIdParams }) => {
    const modal = page.locator('#mercadopago-checkout').contentFrame();
    const congratsRejected = modal.locator('.congrats--rejected');
    const changePaymentMethod = modal.locator('#change_payment_method');
    const cancelPayment = modal.locator('#mp-close-btn');
    const closeAndCancel = modal.locator('.fullscreen-message__content button.andes-button--quiet');
    const emptyCart = page.locator('.cart-empty');

    await choproModalGuestUser(page, siteIdParams.credit_cards.master, siteIdParams.user.document, "CONT");

    await expect(congratsRejected).toBeVisible();
    await expect(changePaymentMethod).toBeVisible();
    await cancelPayment.click();

    await page.waitForLoadState();
    await expect(closeAndCancel).toBeVisible();
    await closeAndCancel.click();
    await page.waitForLoadState();
    await expect(emptyCart).toBeVisible();
})
