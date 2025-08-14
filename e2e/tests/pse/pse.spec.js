import { test, testWithoutFillSteps } from "../test";
import { setProductPrice, getProduct } from "../../flows/manage_product";
import { setConfig } from "../../helpers";
import { expect } from "@playwright/test";
import { payWithPse } from "../../flows/pay_with_pse";
import { fillStepsToCheckout } from "../../flows/fill_steps_to_checkout";

test.beforeAll(async () => {
    await setConfig('payment/mercadopago_adbpayment_pse/active', '1');
    await setConfig('payment/mercadopago_adbpayment_pse/specificcountry', 'CO');
});

test.beforeEach(({ siteIdParams }) => {
    test.skip(siteIdParams.siteId !== 'MCO');
});

testWithoutFillSteps('test successful pse payment, payment must be approved and success page must be shown', async ({ page, siteIdParams }) => {
    const product = await getProduct(page, siteIdParams.url);
    await setProductPrice(page, siteIdParams.admin, 10000.00, product);
    await fillStepsToCheckout(page, siteIdParams.url, siteIdParams.user);

    await payWithPse(page, siteIdParams.user.document);

    await page.waitForTimeout(10000);
    await page.waitForLoadState();

    await expect(page.locator('.checkout-onepage-success')).toBeVisible();

    await setProductPrice(page, siteIdParams.admin, 1000.00, product);
});

testWithoutFillSteps('test pse payment with invalid amount, payment must be rejected', async ({ page, siteIdParams }) => {
    const product = await getProduct(page, siteIdParams.url);
    await setProductPrice(page, siteIdParams.admin, 1.00, product);
    await fillStepsToCheckout(page, siteIdParams.url, siteIdParams.user);

    await payWithPse(page, siteIdParams.user.document);

    await page.waitForLoadState();

    await expect(page.locator('.message-error')).toBeVisible();

    await setProductPrice(page, siteIdParams.admin, 1000.00, product);
});

test('test pse payment empty inputs, payment must be rejected', async ({ page, siteIdParams }) => {
    await payWithPse(page, null, null, null);

    await page.waitForLoadState();

    await expect(page.locator('#mercadopago_adbpayment_pse_payer_document_type-error')).toBeVisible();
    await expect(page.locator('#mercadopago_adbpayment_pse_document_identification-error')).toBeVisible();
    await expect(page.locator('#mercadopago_adbpayment_pse_payer_entity_type-error')).toBeVisible();
    await expect(page.locator('#mercadopago_adbpayment_pse_financial_institutions-error')).toBeVisible();
});
