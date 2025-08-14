import { test, testWithoutFillSteps } from "../test";
import { approvedTest, rejectedTestInvalidAmount, rejectedTestEmptyDocument } from "./general_tests";

test.beforeEach(({ siteIdParams }) => {
    test.skip(siteIdParams.siteId !== 'MPE');
});

test('test successful BCP, BBVA Continental u otros payment, payment must be approved and success page must be shown', async ({page, siteIdParams}) => {
    await approvedTest(page, siteIdParams.user.document, 'pagoefectivo_atm');
});

test('test fail BCP, BBVA Continental u otros payment with empty document fields', async ({page}) => {
    await rejectedTestEmptyDocument(page, 'pagoefectivo_atm');
});

testWithoutFillSteps('test fail BCP, BBVA Continental u otros payment with invalid amount', async ({page, siteIdParams}) => {
    await rejectedTestInvalidAmount(page, siteIdParams, 'pagoefectivo_atm', 5.00);
});
