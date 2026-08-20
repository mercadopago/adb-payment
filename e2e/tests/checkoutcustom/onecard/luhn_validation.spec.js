/**
 * PSW-3966 — Luhn validation on the card-number Secure Field (checkout Custom, 1 card).
 *
 * The plugin creates the cardNumber field with `enableLuhnValidation: true`, so the
 * MP SDK reports a bad checksum in `validityChange` (cause 'invalid_value',
 * details.reason 'luhn'). The renderer shows an inline field error and the submit
 * gate (cardNumberIsValid) blocks tokenization. A format error (length) takes
 * precedence over Luhn, so shortening a number switches the message to length.
 *
 * Uses an Amex test card (BIN recognized, 15 digits, CVV 4). The invalid case flips
 * the last digit — same BIN and length, only the checksum breaks.
 */
import { test } from "./../../test";
import { expect } from "@playwright/test";
import { amexTestCard } from "../../../data/cards";

const CC_FIELD = "mercadopago_adbpayment_cc_number";
const breakLuhn = (d) => d.slice(0, -1) + String((Number(d[d.length - 1]) + 1) % 10);

async function fillCard(page, siteIdParams, number) {
    await page.locator('[type=radio][value="mercadopago_adbpayment_cc"]').click();
    await page.waitForLoadState();
    await page.waitForTimeout(3000);

    const docFieldset = page.locator("#payment_form_cc_personamercadopago_adbpayment_cc");
    if (await docFieldset.isVisible().catch(() => false)) {
        await page.locator("#mercadopago_adbpayment_cc_payer_document_type").selectOption(siteIdParams.user.document.type).catch(() => {});
        await page.locator("#mercadopago_adbpayment_cc_document_identification").fill(siteIdParams.user.document.number).catch(() => {});
    }

    await page.frameLocator('iframe[name="cardNumber"]').locator('[name="cardNumber"]').fill(number);
    await page.frameLocator('iframe[name="expirationMonth"]').locator('[name="expirationMonth"]').fill(amexTestCard.month);
    await page.frameLocator('iframe[name="expirationYear"]').locator('[name="expirationYear"]').fill(amexTestCard.year);
    await page.frameLocator('iframe[name="securityCode"]').locator('[name="securityCode"]').fill(amexTestCard.code);
    await page.locator('[name="payment[card_holder_name]"]').first().fill("APRO");
    await page.waitForTimeout(4000);
}

function readCardState(page) {
    return page.evaluate((fieldId) => {
        const el = document.getElementById(fieldId);
        const control = el && el.closest(".control-mp-iframe");
        const group = el && el.closest(".mercadopago-input-group");
        const fe = group && group.querySelector(".field-error");
        return {
            hasError: !!(control && control.classList.contains("has-error")),
            fieldErrorText: fe ? fe.textContent.trim() : "",
        };
    }, CC_FIELD);
}

test("invalid Luhn shows an inline card-number error before submit", async ({ page, siteIdParams }) => {
    await fillCard(page, siteIdParams, breakLuhn(amexTestCard.number));

    await expect.poll(() => readCardState(page), { timeout: 15000, intervals: [500] }).toMatchObject({ hasError: true });
    const state = await readCardState(page);
    expect(state.hasError).toBe(true);
    expect(state.fieldErrorText.length).toBeGreaterThan(0);
});

test("valid Luhn does not raise a card-number error", async ({ page, siteIdParams }) => {
    await fillCard(page, siteIdParams, amexTestCard.number);

    const state = await readCardState(page);
    expect(state.hasError).toBe(false);
});

test("removing a digit shows the length error, not the Luhn error", async ({ page, siteIdParams }) => {
    await fillCard(page, siteIdParams, breakLuhn(amexTestCard.number));
    await expect.poll(() => readCardState(page), { timeout: 15000, intervals: [500] }).toMatchObject({ hasError: true });
    const luhnState = await readCardState(page);
    expect(luhnState.hasError).toBe(true);

    // Remove one digit → invalid length. The SDK emits both invalid_length and the
    // Luhn error; the length (format) error must win, so the message changes.
    const cn = page.frameLocator('iframe[name="cardNumber"]').locator('[name="cardNumber"]');
    await cn.click();
    await cn.press("End").catch(() => {});
    await cn.press("Backspace");

    // Poll until the message switches away from the Luhn one (the length error takes
    // over) instead of a fixed wait — robust against a slow validityChange.
    await expect.poll(async () => (await readCardState(page)).fieldErrorText, { timeout: 15000, intervals: [500] })
        .not.toBe(luhnState.fieldErrorText);

    const lengthState = await readCardState(page);
    expect(lengthState.hasError).toBe(true);
    // The not.toBe below is sufficient to prove the length error won precedence over
    // Luhn: if Luhn had won instead, the rendered message would be the same static
    // invalidCardMsg regardless of length, and this assertion would fail. Asserting on
    // the SDK's literal "15" would be fragile — that string comes from the SDK, not us.
    expect(lengthState.fieldErrorText).not.toBe(luhnState.fieldErrorText);
});
