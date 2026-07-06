import { test } from "./../test";
import { expect } from "@playwright/test";
import { setConfig } from "../../helpers";

// Alphanumeric CNPJ reference example (RFB Technical Note 49):
// positions 1-12 are alphanumeric, positions 13-14 are numeric check digits.
const VALID_ALPHANUMERIC_CNPJ = '12.ABC.345/01DE-35';
const INVALID_CHECK_DIGIT_CNPJ = '12.ABC.345/01DE-99';
const LETTER_IN_CHECK_DIGIT_CNPJ = '12.ABC.345/01DE-X5';
const REPEATED_CHARACTERS_CNPJ = '00000000000000';
const VALID_NUMERIC_CNPJ = '11.222.333/0001-81';

const TICKET_RADIO = '[type=radio][value="mercadopago_adbpayment_payment_methods_off"]';
const DOCUMENT_TYPE = '#mercadopago_adbpayment_payment_methods_off_payer_document_type';
const DOCUMENT_NUMBER = '#mercadopago_adbpayment_payment_methods_off_document_identification';
const DOCUMENT_NUMBER_ERROR = '#mercadopago_adbpayment_payment_methods_off_document_identification-error';
const TICKET_OPTION = '#payment-methods-off .payment-methods-off-input';
const BILLING_SAME_AS_SHIPPING = '#billing-address-same-as-shipping-mercadopago_adbpayment_payment_methods_off';
const PLACE_ORDER = '#payment_form_mercadopago_adbpayment_payment_methods_off button.action.primary.checkout';

// jQuery validator message ('Please provide a valid document identification.'), matched in
// both pt_BR (MLB store locale) and the en fallback so the assertion is locale-robust.
const INVALID_DOCUMENT_MESSAGE = /documento de identificação válido|valid document identification/i;

// Alphanumeric CNPJ is a Brazil-only (MLB) requirement.
test.beforeEach(({ siteIdParams }) => {
    test.skip(siteIdParams.siteId !== 'MLB');
});

// Enable the off-line (ticket) method and the full BR address required to generate a boleto.
// Requires SELF_CONFIG=true (Magento CLI); otherwise these are no-ops and the store config is used.
test.beforeAll(async () => {
    await setConfig('customer/address/street_lines', '4');
    await setConfig('payment/mercadopago_adbpayment_payment_methods_off/specificcountry', 'BR');
    await setConfig('payment/mercadopago_adbpayment_payment_methods_off/active', '1');
});

async function selectTicketMethod(page) {
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(2000);

    // Selecting this method triggers a payment re-render that can drop the first click,
    // so retry until the document field becomes active.
    const documentNumber = page.locator(DOCUMENT_NUMBER);
    for (let attempt = 0; attempt < 4; attempt++) {
        await page.locator(TICKET_RADIO).click({ force: true });
        await page.waitForTimeout(3000);
        if (await documentNumber.isVisible() && await documentNumber.isEnabled()) {
            break;
        }
    }
    await expect(documentNumber).toBeVisible();
    await expect(documentNumber).toBeEnabled();
}

// Type the document so each keyup fires the mpPayerDocument observable. The field binding uses
// valueUpdate: 'keyup', so a programmatic fill() (input event only) would not run the
// auto-detection subscribe that sets the document type.
async function fillDocument(page, documentNumber) {
    await page.locator(DOCUMENT_NUMBER).pressSequentially(documentNumber, { delay: 20 });
    await page.waitForLoadState();
}

// Select the first available ticket option (e.g. boleto) and reuse the shipping address, so the
// only field left to validate on submit is the document.
async function fillTicketData(page) {
    await page.locator(TICKET_OPTION).first().check();
    await page.waitForLoadState();
    await page.locator(BILLING_SAME_AS_SHIPPING).check();
    await page.waitForLoadState();
}

// Fill a complete, otherwise-valid ticket form with the given document, then submit. The only
// invalid input is the document, so submitting must surface the validator message on its field.
async function expectDocumentRejected(page, documentNumber) {
    await selectTicketMethod(page);
    await fillDocument(page, documentNumber);
    await fillTicketData(page);

    await page.locator(PLACE_ORDER).click();
    await page.waitForLoadState();

    const fieldError = page.locator(DOCUMENT_NUMBER_ERROR);
    await expect(fieldError).toBeVisible();
    await expect(fieldError).toContainText(INVALID_DOCUMENT_MESSAGE);

    // Keep the error on screen briefly so it is captured in recordings.
    await page.waitForTimeout(2000);
}

test('test valid alphanumeric CNPJ is accepted and auto-detected as type CNPJ', async ({ page }) => {
    await selectTicketMethod(page);
    await fillDocument(page, VALID_ALPHANUMERIC_CNPJ);

    // The document type <select> is two-way bound to mpPayerType, which default.js sets
    // from the document length. A 14-char alphanumeric document must resolve to CNPJ.
    await expect(page.locator(DOCUMENT_TYPE)).toHaveValue('CNPJ');
    await expect(page.locator(DOCUMENT_NUMBER_ERROR)).toBeHidden();

    // Keep the accepted state on screen briefly so it is captured in recordings.
    await page.waitForTimeout(2000);
});

test('test valid numeric CNPJ completes the ticket payment', async ({ page }) => {
    await selectTicketMethod(page);
    await fillDocument(page, VALID_NUMERIC_CNPJ);
    await fillTicketData(page);

    await page.locator(PLACE_ORDER).click();
    await page.waitForLoadState();

    await page.waitForSelector('.checkout-onepage-success');
    await expect(page.locator('.checkout-onepage-success')).toBeVisible();
});

test('test alphanumeric CNPJ with an invalid check digit shows a field error', async ({ page }) => {
    await expectDocumentRejected(page, INVALID_CHECK_DIGIT_CNPJ);
});

test('test alphanumeric CNPJ with a letter in a check-digit position shows a field error', async ({ page }) => {
    await expectDocumentRejected(page, LETTER_IN_CHECK_DIGIT_CNPJ);
});

test('test repeated-character CNPJ shows a field error', async ({ page }) => {
    await expectDocumentRejected(page, REPEATED_CHARACTERS_CNPJ);
});
