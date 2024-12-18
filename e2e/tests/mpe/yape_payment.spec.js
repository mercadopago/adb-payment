import { test, expect } from "@playwright/test";
import { mpe } from "../../data/stores";
import addProductToCart from "../../flows/add_product_to_cart";
import fillBillingData from "../../flows/fill_billing_data";
import payWithYape from "../../flows/pay_with_yape";

test('test success pay as guest with yape', async ({page}) => {
    const { url, yapeData, guestUserMPE } = mpe;

    await page.goto(url);

    await addProductToCart(page);
    await fillBillingData(page, guestUserMPE);
    await payWithYape(page, yapeData.yapeApproved);

    const messageSuccess = yapeData.yapeApproved.messageSuccess;
    const regex = new RegExp(`${messageSuccess.ES}|${messageSuccess.PT}|${messageSuccess.EN}`);


    await page.waitForSelector('.checkout-onepage-success');

    await expect(page.getByRole('heading')).toContainText(regex);
});

test('test payment as rejected - Error in authorization request on yape', async ({page}) => {
    const { url, yapeData, guestUserMPE } = mpe;

    await page.goto(url);

    await addProductToCart(page);
    await fillBillingData(page, guestUserMPE);
    await payWithYape(page, yapeData.yapeRejectedCallForAuthorize);

    const messageError = yapeData.yapeRejectedCallForAuthorize.messageError;
    const regex = new RegExp(`${messageError.ES}|${messageError.PT}|${messageError.EN}`);

    await expect(page.getByRole('alert')).toContainText(regex);
});

test('test payment as rejected - Error Insufficient Amount on yape', async ({page}) => {
    const { url, yapeData, guestUserMPE } = mpe;

    await page.goto(url);

    await addProductToCart(page);
    await fillBillingData(page, guestUserMPE);
    await payWithYape(page, yapeData.yapeRejectedInsufficientAmount);

    const messageError = yapeData.yapeRejectedInsufficientAmount.messageError;
    const regex = new RegExp(`${messageError.ES}|${messageError.PT}|${messageError.EN}`);

    await expect(page.getByRole('alert')).toContainText(regex);
});

test('test payment as rejected - Error Other Reason on yape', async ({page}) => {
    const { url, yapeData, guestUserMPE } = mpe;

    await page.goto(url);

    await addProductToCart(page);
    await fillBillingData(page, guestUserMPE);
    await payWithYape(page, yapeData.yapeRejectedOtherReason);

    const messageError = yapeData.yapeRejectedOtherReason.messageError;
    const regex = new RegExp(`${messageError.ES}|${messageError.PT}|${messageError.EN}`);

    await expect(page.getByRole('alert')).toContainText(regex);
});

test('test payment as rejected - Error Not Allowed on yape', async ({page}) => {
    const { url, yapeData, guestUserMPE } = mpe;

    await page.goto(url);

    await addProductToCart(page);
    await fillBillingData(page, guestUserMPE);
    await payWithYape(page, yapeData.yapeRejectedNotAllowed);

    const messageError = yapeData.yapeRejectedNotAllowed.messageError;
    const regex = new RegExp(`${messageError.ES}|${messageError.PT}|${messageError.EN}`);

    await expect(page.getByRole('alert')).toContainText(regex);
});

test('test payment as rejected - Error Max Attempts on yape', async ({page}) => {
    const { url, yapeData, guestUserMPE } = mpe;

    await page.goto(url);

    await addProductToCart(page);
    await fillBillingData(page, guestUserMPE);
    await payWithYape(page, yapeData.yapeRejectedMaxAttempts);

    const messageError = yapeData.yapeRejectedMaxAttempts.messageError;
    const regex = new RegExp(`${messageError.ES}|${messageError.PT}|${messageError.EN}`);

    await expect(page.getByRole('alert')).toContainText(regex);
});

test('test payment as rejected - Error Security Code on yape', async ({page}) => {
    const { url, yapeData, guestUserMPE } = mpe;

    await page.goto(url);

    await addProductToCart(page);
    await fillBillingData(page, guestUserMPE);
    await payWithYape(page, yapeData.yapeRejectedSecurityCode);

    const messageError = yapeData.yapeRejectedSecurityCode.messageError;
    const regex = new RegExp(`${messageError.ES}|${messageError.PT}|${messageError.EN}`);

    await expect(page.getByRole('alert')).toContainText(regex);
});

test('test payment as rejected - Error Form Error or Default on yape', async ({page}) => {
    const { url, yapeData, guestUserMPE } = mpe;

    await page.goto(url);

    await addProductToCart(page);
    await fillBillingData(page, guestUserMPE);
    await payWithYape(page, yapeData.yapeRejectedFormError);

    const messageError = yapeData.yapeRejectedFormError.messageError;
    const regex = new RegExp(`${messageError.ES}|${messageError.PT}|${messageError.EN}`);

    await expect(page.getByRole('alert')).toContainText(regex);
});

test('test form as empty - Empty field on yape phone', async ({page}) => {
  const { url, yapeData, guestUserMPE } = mpe;
  await page.goto(url);

  await addProductToCart(page);
  await fillBillingData(page, guestUserMPE);
  await payWithYape(page, yapeData.yapeEmptyFormError);

  const messageError = yapeData.yapeEmptyFormError.messageError;
  const regex = new RegExp(`${messageError.ES}|${messageError.PT}|${messageError.EN}`);

  await expect(page.locator('#yape-phone-empty')).toHaveText(regex);
  await expect(page.locator('#yape-code-empty')).toHaveText(regex);

  await page.waitForSelector('#yape-phone-empty', { state: 'visible' });
  await page.waitForSelector('#yape-code-empty', { state: 'visible' });

  await expect(page.locator('#yape-phone-empty')).toBeVisible();
  await expect(page.locator('#yape-code-empty')).toBeVisible();

});

test('test form as incomplete - Incomplete field on yape phone', async ({page}) => {
    const { url, yapeData, guestUserMPE } = mpe;
    await page.goto(url);

    await addProductToCart(page);
    await fillBillingData(page, guestUserMPE);
    await payWithYape(page, yapeData.yapeIncompleteFormError);

    const messageError = yapeData.yapeIncompleteFormError.messageError;
    const regex = new RegExp(`${messageError.ES}|${messageError.PT}|${messageError.EN}`);

    await expect(page.locator('#yape-phone-incomplete')).toHaveText(regex);
    await expect(page.locator('#yape-code-incomplete')).toHaveText(regex);

    await page.waitForSelector('#yape-phone-incomplete', { state: 'visible' });
    await page.waitForSelector('#yape-code-incomplete', { state: 'visible' });


    await expect(page.locator('#yape-phone-incomplete')).toBeVisible();
    await expect(page.locator('#yape-code-incomplete')).toBeVisible();

});

test('test form as Invalid - Invalid field on yape phone', async ({page}) => {
const { url, yapeData, guestUserMPE } = mpe;
await page.goto(url);

await addProductToCart(page);
await fillBillingData(page, guestUserMPE);
await payWithYape(page, yapeData.yapeInvalidFormError);

const messageError = yapeData.yapeInvalidFormError.messageError;
const regex = new RegExp(`${messageError.ES}|${messageError.PT}|${messageError.EN}`);

await expect(page.locator('#yape-phone-incorrect')).toHaveText(regex);
await page.waitForTimeout(1000);
await page.locator('#yape-phone').click();
await page.keyboard.press('a');
await page.waitForTimeout(1000);
await page.waitForSelector('#yape-phone-incorrect', { state: 'visible' });
await expect(page.locator('#yape-phone-incorrect')).toBeVisible();
});
