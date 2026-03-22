import { existsSync, rmSync } from 'node:fs';
import * as esbuild from 'esbuild';

const isWatch = process.argv.includes('--watch');
const buildTargets = [];

if (existsSync('assets/dist')) {
  rmSync('assets/dist', { recursive: true, force: true });
}

if (existsSync('assets/src/frontend/init.js')) {
  buildTargets.push({
    entryPoints: ['assets/src/frontend/init.js'],
    bundle: true,
    minify: !isWatch,
    sourcemap: isWatch,
    outfile: 'assets/js/script.js',
    platform: 'browser',
    target: ['es2020']
  });
}

if (existsSync('assets/src/frontend/styles/index.css')) {
  buildTargets.push({
    entryPoints: ['assets/src/frontend/styles/index.css'],
    bundle: true,
    minify: !isWatch,
    sourcemap: isWatch,
    outfile: 'assets/css/style.css'
  });
}

if (existsSync('assets/src/admin/index.js')) {
  buildTargets.push({
    entryPoints: ['assets/src/admin/index.js'],
    bundle: true,
    minify: !isWatch,
    sourcemap: isWatch,
    outfile: 'assets/js/admin.js',
    platform: 'browser',
    target: ['es2020']
  });
}

if (existsSync('assets/src/admin/styles/index.css')) {
  buildTargets.push({
    entryPoints: ['assets/src/admin/styles/index.css'],
    bundle: true,
    minify: !isWatch,
    sourcemap: isWatch,
    outfile: 'assets/css/admin.css'
  });
}

if (buildTargets.length === 0) {
  console.log('No asset entry points found.');
  process.exit(0);
}

if (isWatch) {
  const contexts = await Promise.all(
    buildTargets.map((buildTarget) => esbuild.context(buildTarget))
  );

  await Promise.all(contexts.map((context) => context.watch()));
  console.log('Watching for asset changes...');
} else {
  await Promise.all(buildTargets.map((buildTarget) => esbuild.build(buildTarget)));
  console.log('Build complete.');
}
