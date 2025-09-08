/**
 * Registers a new block variation provided a unique name and an object defining its behavior.
 *
 * @see https://developer.wordpress.org/block-editor/packages/packages-blocks/#registerBlockVariation
 */
import { Path, SVG } from "@wordpress/primitives";
import { registerBlockVariation } from "@wordpress/blocks";
import { createElement } from "@wordpress/element";
import domReady from "@wordpress/dom-ready";

/**
 * Lets webpack process CSS, SASS or SCSS files referenced in JavaScript files.
 *
 * @see https://www.npmjs.com/package/@wordpress/scripts#using-css
 */
import "./editor.scss";

/**
 * Retrieves the translation of text.
 *
 * @see https://developer.wordpress.org/block-editor/packages/packages-i18n/
 */
import { __ } from "@wordpress/i18n";

/**
 * Component to render SVG of DataViz logo icon.
 * @returns JSX.Element
 */
const embedDataVizIcon = createElement(
	SVG,
	{
		xmlns: "http://www.w3.org/2000/svg",
		viewBox: "0 -960 960 960",
		width: "24px",
		height: "24px",
		fill: "currentColor",
	},
	createElement(Path, {
		d: "M280-280h80v-280h-80v280Zm160 0h80v-400h-80v400Zm160 0h80v-160h-80v160ZM200-120q-33 0-56.5-23.5T120-200v-560q0-33 23.5-56.5T200-840h560q33 0 56.5 23.5T840-760v560q0 33-23.5 56.5T760-120H200Zm0-80h560v-560H200v560Zm0-560v560-560Z",
	}),
);

/*
 * New `core/embed` block variation.
 */
const variation = {
	name: "tw-dataviz",
	title: "Data Viz",
	icon: embedDataVizIcon,
	keywords: [__("graph"), __("chart")],
	description: __("Embed an interactove data visualization."),
	patterns: [/^https?:\/\/interactive\.pri\.org\/.+/i],
	attributes: {
		providerNameSlug: "dataviz",
		className: "tw-dataviz",
		previewable: true,
	},
	isDefault: false,
	isActive: (blockAttributes) => {
		const { url } = blockAttributes;
		return /^https?:\/\/interactive\.pri\.org\/.+/i.test(url);
	},
};

registerBlockVariation("core/embed", variation);

/**
 * Add message event handlers that will update iframe height when iframe is resized.
 */
domReady(() => {
	function handleMessage(event) {
		if (event.data.type === "TwDatavizUpdateHeight" && event.data.payload) {
			iframes.forEach((iframe) => {
				if (iframe.contentWindow === event.source) {
					iframe.style.height = `${event.data.payload}px`;
				}
			});
		}
	}

	window.addEventListener("message", handleMessage);
});
