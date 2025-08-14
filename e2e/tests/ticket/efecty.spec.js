import { test, testWithoutFillSteps } from "../test";
import { approvedTestWithMinimumPrice, rejectedTestInvalidAmount, rejectedTestEmptyDocument } from "./general_tests";

test.beforeEach(({ siteIdParams }) => {
    test.skip(siteIdParams.siteId !== 'MCO');
});

testWithoutFillSteps('test successful efecty payment, payment must be approved and success page must be shown', async ({page, siteIdParams}) => {
    await approvedTestWithMinimumPrice(page, siteIdParams, 'efecty', 7000.00);
});

test('test fail efecty payment with empty document fields', async ({page}) => {
    await rejectedTestEmptyDocument(page, 'efecty');
});

testWithoutFillSteps('test fail efecty payment with invalid amount', async ({page, siteIdParams}) => {
    await rejectedTestInvalidAmount(page, siteIdParams, 'efecty', 500.00);
});
