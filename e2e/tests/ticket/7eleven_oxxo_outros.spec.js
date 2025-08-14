import { test, testWithoutFillSteps } from "../test";
import { approvedTest, rejectedTestInvalidAmount } from "./general_tests";

test.beforeEach(({ siteIdParams }) => {
    test.skip(siteIdParams.siteId !== 'MLM');
});

test('test successful 7 eleven payment, payment must be approved and success page must be shown', async ({page}) => {
    await approvedTest(page, null, 'eleven');
});

test('test successful oxxo payment, payment must be approved and success page must be shown', async ({page}) => {
    await approvedTest(page, null, 'oxxo');
});

test('test successful bbva bancomer payment, payment must be approved and success page must be shown', async ({page}) => {
    await approvedTest(page, null, 'bancomer');
});

testWithoutFillSteps('test fail oxxo payment with invalid amount', async ({page, siteIdParams}) => {
    await rejectedTestInvalidAmount(page, siteIdParams, 'eleven', 5.00);
});
