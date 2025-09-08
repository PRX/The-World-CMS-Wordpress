wp.domReady(function () {
  // Make Featured Image opened initially.
  if (!wp.data.select("core/editor").isEditorPanelOpened("featured-image")) {
    wp.data.dispatch("core/editor").toggleEditorPanelOpened("featured-image");
  }

  // Allow only certain embed variants.
  const allowedEmbedBlocks = [
    "datawrapper",
    "instagram",
    "spotify",
    "tiktok",
    "tw-dataviz",
    "twitter",
    "youtube",
    "vimeo",
  ];

  wp.blocks.getBlockVariations("core/embed").forEach(function (blockVariation) {
    if (-1 === allowedEmbedBlocks.indexOf(blockVariation.name)) {
      wp.blocks.unregisterBlockVariation("core/embed", blockVariation.name);
    }
  });

  // Remove image style options.
  wp.blocks.unregisterBlockStyle("core/image", ["default", "rounded"]);

  // Remove text format options.
  wp.richText.unregisterFormatType("core/footnote");
  wp.richText.unregisterFormatType("core/text-color");
  wp.richText.unregisterFormatType("core/image");
});

// Adjust block settings not configurable in `theme.json`.
wp.hooks.addFilter(
  "blocks.registerBlockType",
  "the-world/the-world/log-block-settings",
  (settings, name) => {
    switch (name) {
      case "core/audio":
        // Disable supports for audio block.
        return {
          ...settings,
          supports: {
            align: false,
            anchor: false,
            spacing: {},
          },
        };

      case "core/embed":
        return {
          ...settings,
          variations: settings.variations?.map((variation) => {
            const { name } = variation;

            switch (name) {
              // Disable responsive option for Spotify embeds.
              // Doesn't play well with responsive layouts inside the Spotify iframe.
              case "spotify":
                return {
                  ...variation,
                  attributes: {
                    ...variation.attributes,
                    allowResponsive: false,
                    responsive: false,
                  },
                };
              default:
                return variation;
            }
          }),
        };

      default:
        return settings;
    }
  },
  1000
);
