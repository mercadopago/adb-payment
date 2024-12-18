export default async function(page, user) {
    await page.waitForTimeout(5000);
    await page.getByRole('textbox', { id: 'customer-email' }).first().fill(user.email);
    await page.getByLabel('First Name').fill(user.firstName);
    await page.getByLabel('Last Name').fill(user.lastName);
    await page.getByLabel('Street Address: Line 1').fill(user.address.street);
    await page.waitForTimeout(3000);
    await page.locator('select[name="country_id"]').selectOption(user.address.countryId);
    await page.locator('select[name="region_id"]').selectOption(user.address.regionId);
    await page.locator('input[name="city"]').fill(user.address.city);
    await page.locator('input[name="postcode"]').fill(user.address.zip);
    await page.locator('input[name="telephone"]').fill(user.phone);
    await page.getByRole('button', { name: 'Next' }).click();
}
