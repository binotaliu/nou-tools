import { execFileSync } from 'node:child_process';
import { dirname, extname, resolve } from 'node:path';

import imagemin from 'imagemin';
import imageminGifsicle from 'imagemin-gifsicle';
import imageminMozjpeg from 'imagemin-mozjpeg';
import imageminPngquant from 'imagemin-pngquant';
import imageminSvgo from 'imagemin-svgo';

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

function getPluginsForFile(filePath) {
  const extension = extname(filePath).toLowerCase();

  if (extension === '.png') {
    return [imageminPngquant({ quality: [0.65, 0.8], strip: true })];
  }

  if (extension === '.jpg' || extension === '.jpeg') {
    return [imageminMozjpeg({ quality: 75 })];
  }

  if (extension === '.gif') {
    return [imageminGifsicle({ optimizationLevel: 2 })];
  }

  if (extension === '.svg') {
    return [imageminSvgo({ multipass: true })];
  }

  return [];
}

async function optimizeImages() {
  const relativePaths = getAddedStagedImagePaths();

  if (relativePaths.length === 0) {
    console.log('No newly added staged images to optimize.');
    return;
  }

  const absolutePaths = relativePaths.map((relativePath) => resolve(process.cwd(), relativePath));

  for (const absolutePath of absolutePaths) {
    const plugins = getPluginsForFile(absolutePath);

    if (plugins.length === 0) {
      continue;
    }

    await imagemin([absolutePath], {
      destination: dirname(absolutePath),
      plugins,
    });
  }

  execFileSync('git', ['add', '--', ...relativePaths], { stdio: 'inherit' });

  console.log(`Optimized ${relativePaths.length} newly added image(s).`);
}

optimizeImages().catch((error) => {
  console.error('Failed to optimize staged images.');
  console.error(error);
  process.exit(1);
});
