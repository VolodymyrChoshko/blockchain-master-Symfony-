const esbuild = require('esbuild');
const { esbuildPluginAliasPath } = require('esbuild-plugin-alias-path');
const { esbuildDecorators } = require('@anatine/esbuild-decorators');

const path = require('path');

esbuild.build({
  entryPoints: [
    'src/notifications.ts',
    'src/socketserver.ts',
    'src/pinThumbnails.ts'
  ],
  outdir:     'build',
  bundle:      true,
  sourcemap:   true,
  platform:    'node',
  watch:       process.argv[2] === 'watch',
  minify:      process.env.NODE_ENV === 'production',
  metafile:    process.env.NODE_ENV !== 'production',
  plugins: [
    esbuildPluginAliasPath({
      alias: {
        'utils': path.resolve(`${__dirname}../../assets/js/utils/index.js`),
      }
    }),
    esbuildDecorators(),
  ]
})
  .catch(() => process.exit(1));
