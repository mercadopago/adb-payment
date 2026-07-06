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

const TWOCC_RADIO = '[type=radio][value="mercadopago_adbpayment_twocc"]';
const SECOND_CARD_RADIO = '#mp-second-card-radio';
const DOCUMENT_TYPE = '#mercadopago_adbpayment_twocc_payer_document_type';
const DOCUMENT_NUMBER = '#mercadopago_adbpayment_twocc_document_identification';
const DOCUMENT_NUMBER_ERROR = '#mercadopago_adbpayment_twocc_document_identification-error';
const CARDHOLDER_NAME = '#mercadopago_adbpayment_twocc_cardholder_name';
const INSTALLMENTS = 'select[name="payment[card_installments]"]';
const BILLING_SAME_AS_SHIPPING = '#billing-address-same-as-shipping-mercadopago_adbpayment_twocc';
const PLACE_ORDER = '#payment_form_mercadopago_adbpayment_twocc button.action.primary.checkout';

// jQuery validator message ('Please provide a valid document identification.'), matched in
// both pt_BR (MLB store locale) and the en fallback so the assertion is locale-robust.
const INVALID_DOCUMENT_MESSAGE = /documento de identificação válido|valid document identification/i;

// Alphanumeric CNPJ is a Brazil-only (MLB) requirement.
test.beforeEach(({ siteIdParams }) => {
    test.skip(siteIdParams.siteId !== 'MLB');
});

async function selectTwoCardsMethod(page) {
    await page.locator(TWOCC_RADIO).click();
    await page.waitForLoadState();
    await page.waitForTimeout(2000);
}

// Type the document so each keyup fires the mpPayerDocument observable. The field binding uses
// valueUpdate: 'keyup', so a programmatic fill() (input event only) would not run the
// auto-detection subscribe that sets the document type.
async function fillDocument(page, documentNumber) {
    await page.locator(DOCUMENT_NUMBER).pressSequentially(documentNumber, { delay: 20 });
    await page.waitForLoadState();
}

// Fill the secure fields and cardholder name of the currently active card, then installments.
async function fillActiveCard(page, card, cardHolderName) {
    await page.frameLocator('iframe[name="cardNumber"]').locator('#cardNumber').fill(card.number);
    await page.frameLocator('iframe[name="expirationMonth"]').locator('#expirationMonth').fill(card.month);
    await page.frameLocator('iframe[name="expirationYear"]').locator('#expirationYear').fill(card.year);
    await page.frameLocator('iframe[name="securityCode"]').locator('#securityCode').fill(card.code);
    await page.locator(CARDHOLDER_NAME).first().fill(cardHolderName);

    await page.waitForLoadState();

    // Installments appear only after the BIN is recognized; wait for them explicitly instead of a
    // fixed sleep, but tolerate cards/amounts that do not offer installments.
    await page.locator(INSTALLMENTS).waitFor({ state: 'visible', timeout: 10000 }).catch(() => {});
    if (await page.locator(INSTALLMENTS).isVisible()) {
        await page.locator(INSTALLMENTS).selectOption('1');
    }
}

// Two-card checkout tokenizes both cards, producing more than one /card_tokens request whose
// order can vary with sandbox latency. Collect every payload so the assertion targets whichever
// request carries the document, instead of assuming it is the first /card_tokens captured.
function collectCardTokenPayloads(page) {
    const payloads = [];
    page.on('request', (request) => {
        if (request.url().includes('/card_tokens') && request.method() === 'POST') {
            payloads.push(request.postData() || '');
        }
    });
    return payloads;
}

// Fill both cards (document set on the first one) for a complete, otherwise-valid two-card form.
async function fillBothCards(page, siteIdParams, documentNumber) {
    await selectTwoCardsMethod(page);

    await fillActiveCard(page, siteIdParams.credit_cards.master, APPROVED_CARDHOLDER);
    await fillDocument(page, documentNumber);

    await page.locator(SECOND_CARD_RADIO).click();
    await page.waitForLoadState();
    await page.waitForTimeout(2000);
    await fillActiveCard(page, siteIdParams.credit_cards.visa, APPROVED_CARDHOLDER);

    await page.locator(BILLING_SAME_AS_SHIPPING).check();
    await page.waitForLoadState();
}

// Fill a complete, otherwise-valid two-card form with the given document, then submit. The only
// invalid input is the document, so submitting must surface the validator message on its field.
async function expectDocumentRejected(page, siteIdParams, documentNumber) {
    await fillBothCards(page, siteIdParams, documentNumber);

    await page.locator(PLACE_ORDER).click();
    await page.waitForLoadState();

    const fieldError = page.locator(DOCUMENT_NUMBER_ERROR);
    await expect(fieldError).toBeVisible();
    await expect(fieldError).toContainText(INVALID_DOCUMENT_MESSAGE);

    // Keep the error on screen briefly so it is captured in recordings.
    await page.waitForTimeout(2000);
}

test('test alphanumeric CNPJ is accepted, auto-detected and normalized in the tokenization payload', async ({ page, siteIdParams }) => {
    await selectTwoCardsMethod(page);

    await fillActiveCard(page, siteIdParams.credit_cards.master, APPROVED_CARDHOLDER);
    // Type lowercase to also exercise the client-side uppercase normalization.
    await fillDocument(page, VALID_ALPHANUMERIC_CNPJ_LOWERCASE);

    // Accepted at the field: the type <select> is two-way bound to mpPayerType, which default.js
    // sets from the document length, so a 14-char alphanumeric document must resolve to CNPJ.
    await expect(page.locator(DOCUMENT_TYPE)).toHaveValue('CNPJ');
    await expect(page.locator(DOCUMENT_NUMBER_ERROR)).toBeHidden();

    await page.locator(SECOND_CARD_RADIO).click();
    await page.waitForLoadState();
    await page.waitForTimeout(2000);
    await fillActiveCard(page, siteIdParams.credit_cards.visa, APPROVED_CARDHOLDER);
    await page.locator(BILLING_SAME_AS_SHIPPING).check();

    // Collect every tokenization payload before submitting (both cards are tokenized).
    const tokenPayloads = collectCardTokenPayloads(page);
    await page.locator(PLACE_ORDER).click();

    // MercadoPago does not approve alphanumeric CNPJ yet, so the success page will not appear.
    // Both cards are tokenized in a non-deterministic order, so wait for whichever /card_tokens
    // request carries the document and assert it reached tokenization typed as CNPJ and normalized
    // to uppercase (mask stripped, lowercase upper-cased).
    await expect
        .poll(() => tokenPayloads.some((p) => p.includes(VALID_ALPHANUMERIC_CNPJ_NORMALIZED)), { timeout: 15000 })
        .toBe(true);

    const documentPayload = tokenPayloads.find((p) => p.includes(VALID_ALPHANUMERIC_CNPJ_NORMALIZED));
    expect(documentPayload).toContain('CNPJ');
    expect(documentPayload).not.toContain(VALID_ALPHANUMERIC_CNPJ_LOWERCASE.replace(/\W/g, ''));

    // Keep the post-submit state on screen briefly so it is captured in recordings.
    await page.waitForTimeout(2000);
});

test('test valid numeric CNPJ completes the two-card payment', async ({ page, siteIdParams }) => {
    await fillBothCards(page, siteIdParams, VALID_NUMERIC_CNPJ);

    await page.locator(PLACE_ORDER).click();
    await page.waitForLoadState();

    await page.waitForSelector('.checkout-onepage-success');
    await expect(page.locator('.checkout-onepage-success')).toBeVisible();
});

test('test alphanumeric CNPJ with an invalid check digit shows a field error', async ({ page, siteIdParams }) => {
    await expectDocumentRejected(page, siteIdParams, INVALID_CHECK_DIGIT_CNPJ);
});

test('test alphanumeric CNPJ with a letter in a check-digit position shows a field error', async ({ page, siteIdParams }) => {
    await expectDocumentRejected(page, siteIdParams, LETTER_IN_CHECK_DIGIT_CNPJ);
});

test('test repeated-character CNPJ shows a field error', async ({ page, siteIdParams }) => {
    await expectDocumentRejected(page, siteIdParams, REPEATED_CHARACTERS_CNPJ);
});

test('test uppercase alphanumeric CNPJ has its mask stripped in the tokenization payload', async ({ page, siteIdParams }) => {
    await selectTwoCardsMethod(page);

    await fillActiveCard(page, siteIdParams.credit_cards.master, APPROVED_CARDHOLDER);
    await fillDocument(page, VALID_ALPHANUMERIC_CNPJ);

    // The type <select> must resolve to CNPJ for a 14-char alphanumeric document.
    await expect(page.locator(DOCUMENT_TYPE)).toHaveValue('CNPJ');
    await expect(page.locator(DOCUMENT_NUMBER_ERROR)).toBeHidden();

    await page.locator(SECOND_CARD_RADIO).click();
    await page.waitForLoadState();
    await page.waitForTimeout(2000);
    await fillActiveCard(page, siteIdParams.credit_cards.visa, APPROVED_CARDHOLDER);
    await page.locator(BILLING_SAME_AS_SHIPPING).check();

    // Collect every tokenization payload before submitting (both cards are tokenized).
    const tokenPayloads = collectCardTokenPayloads(page);
    await page.locator(PLACE_ORDER).click();

    // Input is already uppercase, so only mask stripping applies (AC-03.4). Both cards are
    // tokenized in a non-deterministic order, so wait for whichever /card_tokens request carries
    // the document and assert on it.
    await expect
        .poll(() => tokenPayloads.some((p) => p.includes(VALID_ALPHANUMERIC_CNPJ_NORMALIZED)), { timeout: 15000 })
        .toBe(true);

    const documentPayload = tokenPayloads.find((p) => p.includes(VALID_ALPHANUMERIC_CNPJ_NORMALIZED));
    expect(documentPayload).toContain('CNPJ');

    // Keep the post-submit state on screen briefly so it is captured in recordings.
    await page.waitForTimeout(2000);
});
