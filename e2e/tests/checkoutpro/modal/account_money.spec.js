import { test } from "./../../test";
import { expect } from "@playwright/test";
import { choproModalLoginUser } from "../../../flows/checkoutpro/pay_with_account_money";

test('test successful account money payment with chopro modal', async ({ page, siteIdParams }) => {
    const modal = page.locator('#mercadopago-checkout').contentFrame();
    const returnButton = modal.locator('#group_button_back_congrats');
    const congratsApproved = modal.locator('.congrats--approved');

    await choproModalLoginUser(page, siteIdParams.user);

    await expect(congratsApproved).toBeVisible();
    await expect(returnButton).toBeVisible();
    returnButton.click();

    await page.waitForLoadState();
    await page.waitForSelector('.checkout-onepage-success');
})
