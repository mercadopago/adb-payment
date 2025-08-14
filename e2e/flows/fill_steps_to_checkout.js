import addProductToCart from "./add_product_to_cart";
import fillBillingData from './fill_billing_data.js';

export const fillStepsToCheckout = async function (page, url, user) {
  await page.goto(url);
  await page.waitForLoadState();
  await addProductToCart(page);
  await fillBillingData(page, user);
}
