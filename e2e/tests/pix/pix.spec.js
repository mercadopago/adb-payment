import { test } from "../test";
import { expect } from "@playwright/test";
import { payWithPix } from "../../flows/pix/pay_with_pix";

test.beforeEach(({ siteIdParams }) => {
    test.skip(siteIdParams.siteId !== 'MLB');
});

test('test successful payment with pix, payment must be approved and success page must be shown', async ({page, siteIdParams}) => {
    await payWithPix(page, siteIdParams.user.document);

    await page.waitForLoadState();
    await expect(page.locator('.checkout-onepage-success')).toBeVisible();
});

test('test fail payment pix with empty document fields', async ({page, siteIdParams}) => {
    await payWithPix(page, null);

    await page.waitForLoadState();
    await expect(page.locator('#mercadopago_adbpayment_pix_payer_document_type-error')).toBeVisible();
    await expect(page.locator('#mercadopago_adbpayment_pix_document_identification-error')).toBeVisible();
});
