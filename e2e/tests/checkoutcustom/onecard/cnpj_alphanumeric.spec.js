import { test } from "./../../test";
import { expect } from "@playwright/test";

// Alphanumeric CNPJ reference example (RFB Technical Note 49):
// positions 1-12 are alphanumeric, positions 13-14 are numeric check digits.
const VALID_ALPHANUMERIC_CNPJ = '12.ABC.345/01DE-35';
const VALID_ALPHANUMERIC_CNPJ_LOWERCASE = '12.abc.345/01de-35';
const VALID_ALPHANUMERIC_CNPJ_NORMALIZED = '12ABC34501DE35';
const INVALID_CHECK_DIGIT_CNPJ = '12.ABC.345/01DE-99';
const LETTER_IN_CHECK_DIGIT_CNPJ = '12.ABC.345/01DE-X5';
const REPEATED_CHARACTERS_CNPJ = '00000000000000';
const VALID_NUMERIC_CNPJ = '11.222.333/0001-81';

// MercadoPago test cardholder name that forces an approved payment.
const APPROVED_CARDHOLDER = 'APRO';

const CC_RADIO = '[type=radio][value="mercadopago_adbpayment_cc"]';
const DOCUMENT_TYPE = '#mercadopago_adbpayment_cc_payer_document_type';
const DOCUMENT_NUMBER = '#mercadopago_adbpayment_cc_document_identification';
const DOCUMENT_NUMBER_ERROR = '#mercadopago_adbpayment_cc_document_identification-error';
const CARDHOLDER_NAME = '#mercadopago_adbpayment_cc_cardholder_name';
const INSTALLMENTS = '#mercadopago_adbpayment_cc_installments';
const BILLING_SAME_AS_SHIPPING = '#billing-address-same-as-shipping-mercadopago_adbpayment_cc';
const PLACE_ORDER = '#payment_form_mercadopago_adbpayment_cc button.action.primary.checkout';

// jQuery validator message ('Please provide a valid document identification.'), matched in
// both pt_BR (MLB store locale) and the en fallback so the assertion is locale-robust.
const INVALID_DOCUMENT_MESSAGE = /documento de identificação válido|valid document identification/i;

// Alphanumeric CNPJ is a Brazil-only (MLB) requirement.
test.beforeEach(({ siteIdParams }) => {
    test.skip(siteIdParams.siteId !== 'MLB');
});

async function selectCardMethod(page) {
    await page.locator(CC_RADIO).click();
    await page.waitForLoadState();
    // The document field is the next interaction in every test, so use it as the readiness
    // signal instead of a fixed sleep. waitFor() has no 'enabled' state (only attached/
    // detached/visible/hidden), so enablement is asserted with the toBeEnabled() matcher.
    await expect(page.locator(DOCUMENT_NUMBER)).toBeVisible();
    await expect(page.locator(DOCUMENT_NUMBER)).toBeEnabled();
}

// Type the document so each keyup fires the mpPayerDocument observable. The field binding uses
// valueUpdate: 'keyup', so a programmatic fill() (input event only) would not run the
// auto-detection subscribe that sets the document type.
async function fillDocument(page, documentNumber) {
    await page.locator(DOCUMENT_NUMBER).pressSequentially(documentNumber, { delay: 20 });
    await page.waitForLoadState();
}

// Fill the secure card fields (iframes), the cardholder name (cc-specific id, so it is not
// confused with the two-cards form sharing the payment[card_holder_name] name) and installments.
async function fillCardData(page, card, cardHolderName) {
    // The MP SDK mounts secure-field iframes asynchronously after the radio click;
    // selectCardMethod only waits for the document DOM field. Wait for the cardNumber
    // iframe to be ready before filling to avoid a race on slow mounts.
    await page.frameLocator('iframe[name="cardNumber"]').locator('[name="cardNumber"]').waitFor({ state: 'visible' });
    await page.frameLocator('iframe[name="cardNumber"]').locator('[name="cardNumber"]').fill(card.number);
    await page.frameLocator('iframe[name="expirationMonth"]').locator('[name="expirationMonth"]').fill(card.month);
    await page.frameLocator('iframe[name="expirationYear"]').locator('[name="expirationYear"]').fill(card.year);

    await page.locator(CARDHOLDER_NAME).fill(cardHolderName);
    await page.waitForLoadState();

    // Wait for the securityCode iframe remount cycle (PSW-4359/PSW-3972) before filling CVV.
    // Using installments as a proxy is unreliable because getInstallments() and
    // getPaymentMethods() run in parallel — installments can appear while the old iframe is
    // still mounted. .catch() handles unrecognized BINs where no remount occurs.
    await page.locator('iframe[name="securityCode"]').waitFor({ state: 'detached', timeout: 8000 }).catch(() => {});
    await page.frameLocator('iframe[name="securityCode"]').locator('[name="securityCode"]').waitFor({ state: 'visible' });
    await page.frameLocator('iframe[name="securityCode"]').locator('[name="securityCode"]').fill(card.code);

    // Wait for installments separately so we can select them.
    await page.locator(INSTALLMENTS).waitFor({ state: 'visible', timeout: 10000 }).catch(() => {});
    if (await page.locator(INSTALLMENTS).isVisible()) {
        await page.locator(INSTALLMENTS).selectOption('1');
    }

    await page.locator(BILLING_SAME_AS_SHIPPING).check();
    await page.waitForLoadState();
}

// Fill a complete, otherwise-valid card form with the given document, then submit. The only
// invalid input is the document, so submitting must surface the validator message on its field.
async function expectDocumentRejected(page, card, documentNumber) {
    await selectCardMethod(page);
    await fillDocument(page, documentNumber);
    await fillCardData(page, card, APPROVED_CARDHOLDER);

    await page.locator(PLACE_ORDER).click();
    await page.waitForLoadState();

    const fieldError = page.locator(DOCUMENT_NUMBER_ERROR);
    await expect(fieldError).toBeVisible();
    await expect(fieldError).toContainText(INVALID_DOCUMENT_MESSAGE);

    // Keep the error on screen briefly so it is captured in recordings.
    await page.waitForTimeout(2000);
}

test('test alphanumeric CNPJ is accepted, auto-detected and normalized in the tokenization payload', async ({ page, siteIdParams }) => {
    await selectCardMethod(page);

    // Type lowercase to also exercise the client-side uppercase normalization.
    await fillDocument(page, VALID_ALPHANUMERIC_CNPJ_LOWERCASE);

    // Accepted at the field: the type <select> is two-way bound to mpPayerType, which default.js
    // sets from the document length, so a 14-char alphanumeric document must resolve to CNPJ.
    await expect(page.locator(DOCUMENT_TYPE)).toHaveValue('CNPJ');
    await expect(page.locator(DOCUMENT_NUMBER_ERROR)).toBeHidden();

    // Capture the MP tokenization request that the place order triggers.
    const cardTokenRequest = page.waitForRequest(
        (request) => request.url().includes('/card_tokens') && request.method() === 'POST'
    );

    await fillCardData(page, siteIdParams.credit_cards.visa, APPROVED_CARDHOLDER);
    await page.locator(PLACE_ORDER).click();

    // MercadoPago does not approve alphanumeric CNPJ yet, so the success page will not appear.
    // The furthest verifiable point is the tokenization payload: the document must reach it
    // typed as CNPJ and normalized to uppercase (mask stripped, lowercase upper-cased).
    const payload = (await cardTokenRequest).postData() || '';
    expect(payload).toContain(VALID_ALPHANUMERIC_CNPJ_NORMALIZED);
    expect(payload).toContain('CNPJ');
    expect(payload).not.toContain(VALID_ALPHANUMERIC_CNPJ_LOWERCASE.replace(/\W/g, ''));

    // Keep the post-submit state on screen briefly so it is captured in recordings.
    await page.waitForTimeout(2000);
});

test('test valid numeric CNPJ completes the credit card payment', async ({ page, siteIdParams }) => {
    await selectCardMethod(page);
    await fillDocument(page, VALID_NUMERIC_CNPJ);
    await fillCardData(page, siteIdParams.credit_cards.visa, APPROVED_CARDHOLDER);

    await page.locator(PLACE_ORDER).click();
    await page.waitForLoadState();

    await page.waitForSelector('.checkout-onepage-success');
    await expect(page.locator('.checkout-success')).toBeVisible();
});

test('test alphanumeric CNPJ with an invalid check digit shows a field error', async ({ page, siteIdParams }) => {
    await expectDocumentRejected(page, siteIdParams.credit_cards.visa, INVALID_CHECK_DIGIT_CNPJ);
});

test('test alphanumeric CNPJ with a letter in a check-digit position shows a field error', async ({ page, siteIdParams }) => {
    await expectDocumentRejected(page, siteIdParams.credit_cards.visa, LETTER_IN_CHECK_DIGIT_CNPJ);
});

test('test repeated-character CNPJ shows a field error', async ({ page, siteIdParams }) => {
    await expectDocumentRejected(page, siteIdParams.credit_cards.visa, REPEATED_CHARACTERS_CNPJ);
});

test('test uppercase alphanumeric CNPJ has its mask stripped in the tokenization payload', async ({ page, siteIdParams }) => {
    await selectCardMethod(page);
    await fillDocument(page, VALID_ALPHANUMERIC_CNPJ);

    // The type <select> must resolve to CNPJ for a 14-char alphanumeric document.
    await expect(page.locator(DOCUMENT_TYPE)).toHaveValue('CNPJ');
    await expect(page.locator(DOCUMENT_NUMBER_ERROR)).toBeHidden();

    // Capture the MP tokenization request that the place order triggers.
    const cardTokenRequest = page.waitForRequest(
        (request) => request.url().includes('/card_tokens') && request.method() === 'POST'
    );

    await fillCardData(page, siteIdParams.credit_cards.visa, APPROVED_CARDHOLDER);
    await page.locator(PLACE_ORDER).click();

    // Input is already uppercase, so only mask stripping applies (AC-03.4).
    const payload = (await cardTokenRequest).postData() || '';
    expect(payload).toContain(VALID_ALPHANUMERIC_CNPJ_NORMALIZED);
    expect(payload).toContain('CNPJ');

    // Keep the post-submit state on screen briefly so it is captured in recordings.
    await page.waitForTimeout(2000);
});
