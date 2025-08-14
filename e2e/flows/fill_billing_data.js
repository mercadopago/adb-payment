export default async function (page, user) {
    await page.waitForLoadState();
    await page.locator('#customer-email').fill(user.email ?? 'testuser@testuser.com');
    await page.getByLabel('First Name').fill(user.firstName);
    await page.getByLabel('Last Name').fill(user.lastName);

    await page.getByLabel('Street Address: Line 1').fill(user.address.street);
    await page.waitForLoadState();
    await fill_billing_address(page, user);

    await page.waitForLoadState();
    await page.locator('select[name="country_id"]').selectOption(user.address.countryId);
    await page.locator('select[name="region_id"]').selectOption(user.address.regionId);
    await page.locator('input[name="city"]').fill(user.address.city);
    await page.locator('input[name="postcode"]').fill(user.address.zip);
    await page.locator('input[name="telephone"]').fill(user.phone);

    await page.locator('#checkout-shipping-method-load tr:first-child [type=radio]').click();
    await page.getByRole('button', { name: 'Next' }).click();
}

async function fill_billing_address(page, user) {
    const fields = [
        { label: 'Street Address: Line 2', value: user.address.number },
        { label: 'Street Address: Line 3', value: user.address.complement },
        { label: 'Street Address: Line 4', value: user.address.neighborhood }
    ];

    for (const field of fields) {
        const fieldLocator = page.getByLabel(field.label);
        if (await fieldLocator.isVisible()) {
            await fieldLocator.fill(field.value);
        }
    }
}
