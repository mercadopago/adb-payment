import { execSync } from 'child_process';

async function runCommand(command) {
    if (process.env.SELF_CONFIG !== 'true') {
        return;
    }

    const prefix = process.env.USING_DOCKER === 'true' ? 'docker exec magento_php ' : `${__dirname}/../`;
    execSync(`${prefix}bin/magento ${command}`, { stdio: 'ignore' });
}

export async function setConfig(path, value) {
    await runCommand(`config:set ${path} ${value}`);
};

export async function setBinary(value) {
    await setConfig('payment/mercadopago_adbpayment_cc/can_initialize', value);
}

export async function turnOnBinary() {
    await setBinary('0');
}

export async function turnOffBinary() {
    await setBinary('1');
}

export async function updateIndexCatalog() {
    await runCommand('index:reindex');
}
