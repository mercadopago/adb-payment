import { test } from "./../../test";
import { turnOffBinary } from "../../../helpers";
import { expect } from "@playwright/test";
import { payWithCardEmptyFields } from "../../../flows/checkoutcustom/pay_with_one_card";
import { approvedTest, rejectedTest } from "./general_tests";

test.beforeAll(async () => {
    await turnOffBinary();
});

test('test credit successful payment with one card, payment must be approved and success page must be shown', async ({ page, siteIdParams }) => {
    await approvedTest(page, siteIdParams, siteIdParams.credit_cards.visa, "APRO");
});

test('test credit rejected payment with one card, payment must be rejected and error page must be shown', async ({ page, siteIdParams }) => {
    await rejectedTest(page, siteIdParams, siteIdParams.credit_cards.visa, "OTHE");
});

test('test credit pending payment with one card, payment must be approved and success page must be shown', async ({ page, siteIdParams }) => {
    await approvedTest(page, siteIdParams, siteIdParams.credit_cards.visa, "CONT");
});

test('test credit fail payment with one card and empty identification and card fiels', async ({ page }) => {
    await payWithCardEmptyFields(page);

    await page.waitForLoadState();
    await page.waitForTimeout(2000);
    await expect(page.locator('#mercadopago_adbpayment_cc_payer_document_type-error')).toBeVisible();
    await expect(page.locator('#mercadopago_adbpayment_cc_document_identification-error')).toBeVisible();
    await expect(page.locator('#mercadopago_adbpayment_cc_cardholder_name-error')).toBeVisible();
});

test('test debit successful payment with one card, payment must be approved and success page must be shown', async ({ page, siteIdParams }) => {
    await approvedTest(page, siteIdParams, siteIdParams.debit_card, "APRO");
});

test('test debit rejected payment with one card, payment must be rejected and error page must be shown', async ({ page, siteIdParams }) => {
    await rejectedTest(page, siteIdParams, siteIdParams.debit_card, "OTHE");
});

test('test debit pending payment with one card, payment must be approved and success page must be shown', async ({ page, siteIdParams }) => {
    await approvedTest(page, siteIdParams, siteIdParams.debit_card, "CONT");
});
