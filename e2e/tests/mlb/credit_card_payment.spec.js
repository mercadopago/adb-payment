import { test, expect } from "@playwright/test";
import { mlb } from "../../data/stores";
import addProductToCart from "../../flows/add_product_to_cart";
import fillBillingData from "../../flows/fill_billing_data";
import payWithCreditCard from "../../flows/pay_with_credit_card";

test('test success pay as guest with master', async ({page}) => {
    const { url, cards, guestUserMLB } = mlb;

    await page.goto(url);

    await addProductToCart(page);
    await fillBillingData(page, guestUserMLB);
    await payWithCreditCard(page, cards.masterApro);

    await page.waitForSelector('.checkout-onepage-success');

    await expect(page.getByRole('heading')).toContainText('Gracias por su compra!');
});
