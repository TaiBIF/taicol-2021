const BASE_Z = 2000;
const STEP = 10;
let counter = 0;

export function nextZ() {
    counter += 1;
    return BASE_Z + counter * STEP;
}