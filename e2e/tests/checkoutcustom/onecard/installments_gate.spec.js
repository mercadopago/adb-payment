/**
 * PSW-3968 — Unified pre-submit gate: installments check (Checkout Custom, 1 card).
 *
 * With a recognized BIN the installments <select> is rendered. Placing the order
 * without choosing an installment must surface a jQuery inline validation error on
 * the select field (data-validate required) and must NOT reach the success page —
 * before PSW-3968 this failed silently inside generateToken.
 */
import { test } from "./../../test";
import { expect } from "@playwright/test";
import { amexTestCard } from "../../../data/cards";

async function fillCardWithoutInstallments(page, siteIdParams) {
    await page.waitForLoadState();
    await page.locator('[type=radio][value="mercadopago_adbpayment_cc"]').click();
    await page.waitForLoadState();
    await page.waitForTimeout(3000);

    const docFieldset = page.locator("#payment_form_cc_personamercadopago_adbpayment_cc");
    if (await docFieldset.isVisible().catch(() => false)) {
        await page.locator("#mercadopago_adbpayment_cc_payer_document_type").selectOption(siteIdParams.user.document.type).catch(() => {});
        await page.locator("#mercadopago_adbpayment_cc_document_identification").fill(siteIdParams.user.document.number).catch(() => {});
    }

    await page.frameLocator('iframe[name="cardNumber"]').locator('[name="cardNumber"]').waitFor({ state: 'visible' });
    await page.frameLocator('iframe[name="cardNumber"]').locator('[name="cardNumber"]').fill(amexTestCard.number);
    await page.frameLocator('iframe[name="expirationMonth"]').locator('[name="expirationMonth"]').fill(amexTestCard.month);
    await page.frameLocator('iframe[name="expirationYear"]').locator('[name="expirationYear"]').fill(amexTestCard.year);
    await page.locator('[name="payment[card_holder_name]"]').first().fill("APRO");
    await page.waitForLoadState();
    // Amex BIN lookup (PSW-4359/PSW-3972) unmounts then remounts the securityCode iframe.
    // Wait for the detach→visible cycle instead of a fixed delay.
    await page.locator('iframe[name="securityCode"]').waitFor({ state: 'detached', timeout: 8000 }).catch(() => {});
    await page.frameLocator('iframe[name="securityCode"]').locator('[name="securityCode"]').waitFor({ state: 'visible' });
    await page.frameLocator('iframe[name="securityCode"]').locator('[name="securityCode"]').fill(amexTestCard.code);

    await page.locator('#billing-address-same-as-shipping-mercadopago_adbpayment_cc').check();
    await page.waitForLoadState();
}

test("placing the order without selecting installments is blocked with an error", async ({ page, siteIdParams }) => {
    await fillCardWithoutInstallments(page, siteIdParams);

    // Precondition: the installments select is rendered for the recognized BIN.
    await expect(page.locator('select[name="payment[card_installments]"]')).toBeVisible({ timeout: 15000 });

    await page.locator('#payment_form_mercadopago_adbpayment_cc button.action.primary.checkout').click();

    // data-validate required on the select causes jQuery to render an inline validation
    // error on the field itself rather than a global banner.
    const installmentsSelect = page.locator('select[name="payment[card_installments]"]');
    await expect(installmentsSelect).toHaveClass(/mage-error/, { timeout: 10000 });
    await expect(page.locator('.checkout-onepage-success')).toHaveCount(0);
});

test("selecting installments lets the gate proceed past the installments check", async ({ page, siteIdParams }) => {
    await fillCardWithoutInstallments(page, siteIdParams);

    const installmentsSelect = page.locator('select[name="payment[card_installments]"]');
    await expect(installmentsSelect).toBeVisible({ timeout: 15000 });
    const firstOption = await installmentsSelect.locator('option:not([value=""])').first().getAttribute('value');
    await installmentsSelect.selectOption(firstOption);

    await page.locator('#payment_form_mercadopago_adbpayment_cc button.action.primary.checkout').click();
    await page.waitForLoadState();

    // The installments gate passed: either no error banner, or the banner is not about installments.
    // Do not assert on the final payment outcome (sandbox-dependent).
    const banner = page.locator('.message.message-error.error');
    const bannerVisible = await banner.isVisible().catch(() => false);

    if (bannerVisible) {
        await expect(banner).not.toContainText(/installments|parcelas|cuotas|mensualidades/i);
    } else {
        // Gate passed and no error — confirm the form did not reset to step 1
        await expect(page.locator('#payment_form_mercadopago_adbpayment_cc')).toBeVisible();
    }
});
