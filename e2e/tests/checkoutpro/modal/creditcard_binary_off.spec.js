import { test } from "./../../test";
import { turnOffBinary } from "../../../helpers";
import { expect } from "@playwright/test";
import { choproModalGuestUser } from "../../../flows/checkoutpro/pay_with_creditcard";

test.beforeAll(async () => {
    await turnOffBinary();
});

test('test successful payment with chopro modal, payment must be approved and success page must be shown', async ({ page, siteIdParams }) => {
    const modal = page.locator('#mercadopago-checkout').contentFrame();
    const returnButton = modal.locator('#group_button_back_congrats');
    const congratsApproved = modal.locator('.congrats--approved');

    await choproModalGuestUser(page, siteIdParams.credit_cards.master, siteIdParams.user.document, "APRO");

    await expect(congratsApproved).toBeVisible();
    await expect(returnButton).toBeVisible();
    returnButton.click();

    await page.waitForLoadState();
    await page.waitForSelector('.checkout-onepage-success');
})

test('test rejected payment with chopro modal, change payment method, other payment options must be shown', async ({ page, siteIdParams }) => {
    const modal = page.locator('#mercadopago-checkout').contentFrame();
    const changePaymentMethod = modal.locator('#change_payment_method');
    const otherPaymentOptions = modal.locator('.payment-option-desktop-screen__content');

    await choproModalGuestUser(page, siteIdParams.credit_cards.master, siteIdParams.user.document, "OTHE");

    await expect(changePaymentMethod).toBeVisible();
    await changePaymentMethod.click();

    await page.waitForLoadState();
    await expect(otherPaymentOptions).toBeVisible();
})

test('test cancelled payment with chopro modal after reject payment', async ({ page, siteIdParams }) => {
    const modal = page.locator('#mercadopago-checkout').contentFrame();
    const congratsRejected = modal.locator('.congrats--rejected');
    const changePaymentMethod = modal.locator('#change_payment_method');
    const cancelPayment = modal.locator('#mp-close-btn');
    const closeAndCancel = modal.locator('.fullscreen-message__content button.andes-button--quiet');
    const emptyCart = page.locator('.cart-empty');

    await choproModalGuestUser(page, siteIdParams.credit_cards.master, siteIdParams.user.document, "OTHE");

    await expect(congratsRejected).toBeVisible();
    await expect(changePaymentMethod).toBeVisible();
    await cancelPayment.click();

    await page.waitForLoadState();
    await expect(closeAndCancel).toBeVisible();
    await closeAndCancel.click();
    await page.waitForLoadState();
    await expect(emptyCart).toBeVisible();
})

test('test pending payment with chopro modal, binary must be off, payment must be approved and success page must be shown', async ({ page, siteIdParams }) => {
    const modal = page.locator('#mercadopago-checkout').contentFrame();
    const returnButton = modal.locator('#button');
    const congratsPending = modal.locator('.congrats--recover');
    const emptyCart = modal.locator('.cart-empty');

    await choproModalGuestUser(page, siteIdParams.credit_cards.master, siteIdParams.user.document, "CONT");

    await expect(congratsPending).toBeVisible();
    await expect(returnButton).toBeVisible();
    returnButton.click();

    await page.waitForLoadState();
    await expect(emptyCart).toBeVisible();
})
