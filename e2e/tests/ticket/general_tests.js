import { payWithTicket } from "../../flows/ticket/pay_with_ticket";
import { expect } from "@playwright/test";
import { fillStepsToCheckout } from "../../flows/fill_steps_to_checkout";
import { setProductPrice, getProduct } from "../../flows/manage_product";

export async function approvedTest(page, document, method) {
    await payWithTicket(page, document, method);
    await page.waitForLoadState();

    await page.waitForURL('**/success/**');
    await expect(page.locator('.checkout-onepage-success')).toBeVisible();
}

export async function approvedTestWithMinimumPrice(page, siteIdParams, method, price) {
    const product = await getProduct(page, siteIdParams.url);
    await setProductPrice(page, siteIdParams.admin, price, product);

    await fillStepsToCheckout(page, siteIdParams.url, siteIdParams.user);
    await payWithTicket(page, siteIdParams.user.document, method);
    await page.waitForLoadState();

    await expect(page.locator('.checkout-onepage-success')).toBeVisible();
}

export async function rejectedTestEmptyDocument(page, method) {
    await payWithTicket(page, null, method);
    await page.waitForLoadState();

    await expect(page.locator('#mercadopago_adbpayment_payment_methods_off_payer_document_type-error')).toBeVisible();
    await expect(page.locator('#mercadopago_adbpayment_payment_methods_off_document_identification-error')).toBeVisible();
}

export async function rejectedTestInvalidAmount(page, siteIdParams, method, price) {
    const product = await getProduct(page, siteIdParams.url);
    await setProductPrice(page, siteIdParams.admin, price, product);

    await fillStepsToCheckout(page, siteIdParams.url, siteIdParams.user);
    await payWithTicket(page, siteIdParams.user.document, method);

    await page.waitForLoadState();
    await expect(page.locator('.message-error')).toBeVisible();

    await setProductPrice(page, siteIdParams.admin, 1000.00, product);
}
