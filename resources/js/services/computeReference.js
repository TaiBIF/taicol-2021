/**
 * Compute reference title
 */
import { subTitle, title } from '../utils/preview/reference';
import camelCaseKeys from 'camelcase-keys';

const argv = require('minimist')(global.process.argv.slice(2));

try {
    console.log(
        title(
            camelCaseKeys(
                JSON.parse(argv.r),
                { deep: true, exclude: ['zh-tw', 'en-us'] },
            ) ?? {},
        ),
    );
    console.log(
        subTitle(
            camelCaseKeys(
                JSON.parse(argv.r),
                { deep: true, exclude: ['zh-tw', 'en-us'] },
            ) ?? {},
        ),
    );
} catch (e) {
    console.log(e);
    console.log('-');
}

