import { test } from "./../test";
import { expect } from "@playwright/test";
import { yapeData } from "../../data/yape";
import { setConfig } from "../../helpers";
import payWithYape from "../../flows/yape/pay_with_yape";

test.beforeAll(async () => {
    await setConfig('payment/mercadopago_adbpayment_yape/active', '1');
});

test.beforeEach(({ siteIdParams }) => {
    test.skip(siteIdParams.siteId !== 'MPE');
});

test('test success pay as guest with yape', async ({ page, siteIdParams }) => {
    await payWithYape(page, yapeData.approved);

    const messageSuccess = yapeData.approved.messageSuccess;
    const regex = new RegExp(`${messageSuccess.ES}|${messageSuccess.PT}|${messageSuccess.EN}`);

    await page.waitForSelector('.checkout-onepage-success');

    await expect(page.getByRole('heading')).toContainText(regex);
});

test('test payment as rejected - Error in authorization request on yape', async ({ page, siteIdParams }) => {
    await payWithYape(page, yapeData.rejectedCallForAuthorize);

    const messageError = yapeData.rejectedCallForAuthorize.messageError;
    const regex = new RegExp(`${messageError.ES}|${messageError.PT}|${messageError.EN}`);

    await expect(page.getByRole('alert')).toContainText(regex);
});

test('test payment as rejected - Error Insufficient Amount on yape', async ({ page, siteIdParams }) => {
    await payWithYape(page, yapeData.rejectedInsufficientAmount);

    const messageError = yapeData.rejectedInsufficientAmount.messageError;
    const regex = new RegExp(`${messageError.ES}|${messageError.PT}|${messageError.EN}`);

    await expect(page.getByRole('alert')).toContainText(regex);
});

test('test payment as rejected - Error Other Reason on yape', async ({ page, siteIdParams }) => {
    await payWithYape(page, yapeData.rejectedOtherReason);

    const messageError = yapeData.rejectedOtherReason.messageError;
    const regex = new RegExp(`${messageError.ES}|${messageError.PT}|${messageError.EN}`);

    await expect(page.getByRole('alert')).toContainText(regex);
});

test('test payment as rejected - Error Not Allowed on yape', async ({ page, siteIdParams }) => {
    await payWithYape(page, yapeData.rejectedNotAllowed);

    const messageError = yapeData.rejectedNotAllowed.messageError;
    const regex = new RegExp(`${messageError.ES}|${messageError.PT}|${messageError.EN}`);

    await expect(page.getByRole('alert')).toContainText(regex);
});

test('test payment as rejected - Error Max Attempts on yape', async ({ page, siteIdParams }) => {
    await payWithYape(page, yapeData.rejectedMaxAttempts);

    const messageError = yapeData.rejectedMaxAttempts.messageError;
    const regex = new RegExp(`${messageError.ES}|${messageError.PT}|${messageError.EN}`);

    await expect(page.getByRole('alert')).toContainText(regex);
});

test('test payment as rejected - Error Security Code on yape', async ({ page, siteIdParams }) => {
    await payWithYape(page, yapeData.rejectedSecurityCode);

    const messageError = yapeData.rejectedSecurityCode.messageError;
    const regex = new RegExp(`${messageError.ES}|${messageError.PT}|${messageError.EN}`);

    await expect(page.getByRole('alert')).toContainText(regex);
});

test('test payment as rejected - Error Form Error or Default on yape', async ({ page, siteIdParams }) => {
    await payWithYape(page, yapeData.rejectedFormError);

    const messageError = yapeData.rejectedFormError.messageError;
    const regex = new RegExp(`${messageError.ES}|${messageError.PT}|${messageError.EN}`);

    await expect(page.getByRole('alert')).toContainText(regex);
});

test('test form as empty - Empty field on yape phone', async ({ page, siteIdParams }) => {
    await payWithYape(page, yapeData.emptyFormError);

    const messageError = yapeData.emptyFormError.messageError;
    const regex = new RegExp(`${messageError.ES}|${messageError.PT}|${messageError.EN}`);

    await expect(page.locator('#yape-phone-empty')).toHaveText(regex);
    await expect(page.locator('#yape-code-empty')).toHaveText(regex);

    await page.waitForSelector('#yape-phone-empty', { state: 'visible' });
    await page.waitForSelector('#yape-code-empty', { state: 'visible' });

    await expect(page.locator('#yape-phone-empty')).toBeVisible();
    await expect(page.locator('#yape-code-empty')).toBeVisible();

});

test('test form as incomplete - Incomplete field on yape phone', async ({ page, siteIdParams }) => {
    await payWithYape(page, yapeData.incompleteFormError);

    const messageError = yapeData.incompleteFormError.messageError;
    const regex = new RegExp(`${messageError.ES}|${messageError.PT}|${messageError.EN}`);

    await expect(page.locator('#yape-phone-incomplete')).toHaveText(regex);
    await expect(page.locator('#yape-code-incomplete')).toHaveText(regex);

    await page.waitForSelector('#yape-phone-incomplete', { state: 'visible' });
    await page.waitForSelector('#yape-code-incomplete', { state: 'visible' });


    await expect(page.locator('#yape-phone-incomplete')).toBeVisible();
    await expect(page.locator('#yape-code-incomplete')).toBeVisible();

});

test('test form as Invalid - Invalid field on yape phone', async ({ page, siteIdParams }) => {
    await payWithYape(page, yapeData.invalidFormError);

    const messageError = yapeData.invalidFormError.messageError;
    const regex = new RegExp(`${messageError.ES}|${messageError.PT}|${messageError.EN}`);

    await expect(page.locator('#yape-phone-incorrect')).toHaveText(regex);
    await page.waitForTimeout(1000);
    await page.locator('#yape-phone').click();
    await page.keyboard.press('a');
    await page.waitForTimeout(1000);
    await page.waitForSelector('#yape-phone-incorrect', { state: 'visible' });
    await expect(page.locator('#yape-phone-incorrect')).toBeVisible();
});
