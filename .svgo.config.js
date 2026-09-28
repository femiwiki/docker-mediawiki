/**
 * SVGO Configuration
 * Recommended options from:
 * https://www.mediawiki.org/wiki/Manual:Coding_conventions/SVG#Exemplified_safe_configuration
 */
/** @type {import('svgo').Config} */
module.exports = {
  multipass: true,
  plugins: [
    {
      name: 'preset-default',
      params: {
        overrides: {
          cleanupIds: false,
          removeDesc: false,
          removeTitle: false,
          removeViewBox: false,
          // Keep the XML declaration: without it libmagic, and so MediaWiki's CSSMin,
          // reads an SVG as text/plain rather than image/svg+xml.
          removeXMLProcInst: false,
          sortAttrs: true,
        },
      },
    },
    'removeRasterImages',
  ],
  // The indent `--pretty` uses. SVGO takes the platform's EOL; git normalises it
  // to LF on commit when core.autocrlf is true or input.
  js2svg: {
    indent: '\t',
    pretty: true,
  },
};
