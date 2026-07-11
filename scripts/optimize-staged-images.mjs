import { execFileSync } from 'node:child_process';
import { readFile, writeFile } from 'node:fs/promises';
import { extname, resolve } from 'node:path';

import imageminSvgo from 'imagemin-svgo';
import sharp from 'sharp';

const supportedExtensions = new Set(['.png', '.jpg', '.jpeg', '.gif', '.svg']);

function getAddedStagedImagePaths() {
  const output = execFileSync(
    'git',
    ['diff', '--cached', '--name-only', '--diff-filter=A', '--'],
    { encoding: 'utf8' }
  ).trim();

  if (output.length === 0) {
    return [];
  }

  return output
    .split('\n')
    .map((path) => path.trim())
    .filter((path) => path.length > 0)
    .filter((path) => supportedExtensions.has(extname(path).toLowerCase()));
}

async function optimizeImage(absolutePath) {
  const extension = extname(absolutePath).toLowerCase();
  const original = await readFile(absolutePath);

  if (extension === '.png') {
    return sharp(original).png({ quality: 80, palette: true }).toBuffer();
  }

  if (extension === '.jpg' || extension === '.jpeg') {
    return sharp(original).jpeg({ quality: 75, mozjpeg: true }).toBuffer();
  }

  if (extension === '.gif') {
    return sharp(original, { animated: true }).gif().toBuffer();
  }

  if (extension === '.svg') {
    return imageminSvgo({ multipass: true })(original);
  }

  return null;
}

async function optimizeImages() {
  const relativePaths = getAddedStagedImagePaths();

  if (relativePaths.length === 0) {
    console.log('No newly added staged images to optimize.');
    return;
  }

  const absolutePaths = relativePaths.map((relativePath) => resolve(process.cwd(), relativePath));

  for (const absolutePath of absolutePaths) {
    const optimized = await optimizeImage(absolutePath);

    if (!optimized) {
      continue;
    }

    await writeFile(absolutePath, optimized);
  }

  execFileSync('git', ['add', '--', ...relativePaths], { stdio: 'inherit' });

  console.log(`Optimized ${relativePaths.length} newly added image(s).`);
}

optimizeImages().catch((error) => {
  console.error('Failed to optimize staged images.');
  console.error(error);
  process.exit(1);
});
