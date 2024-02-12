/**
 * @param val
 * @returns {string|*}
 */
const trim = (val) => {
  if (!val || typeof val !== 'string') {
    return val;
  }

  return val.trim();
};

class DocMeta {
  /**
   * @param {Document} doc
   * @returns {{lang: (string|string), domLang: Element | SVGSymbolElement | SVGMetadataElement | SVGUseElement | SVGFEImageElement | SVGPathElement | SVGViewElement | SVGFEConvolveMatrixElement | SVGFECompositeElement | SVGEllipseElement | SVGFEOffsetElement | SVGTextElement | SVGDefsElement | SVGFETurbulenceElement | SVGImageElement | SVGFEFuncGElement | SVGTSpanElement | SVGClipPathElement | SVGLinearGradientElement | SVGFEFuncRElement | SVGScriptElement | SVGFEColorMatrixElement | SVGFEComponentTransferElement | SVGStopElement | SVGMarkerElement | SVGFEMorphologyElement | SVGFEMergeElement | SVGFEPointLightElement | SVGForeignObjectElement | SVGFEDiffuseLightingElement | SVGStyleElement | SVGFEBlendElement | SVGCircleElement | SVGPolylineElement | SVGDescElement | SVGFESpecularLightingElement | SVGLineElement | SVGFESpotLightElement | SVGFETileElement | SVGPatternElement | SVGTitleElement | SVGSwitchElement | SVGRectElement | SVGFEDisplacementMapElement | SVGFEFuncAElement | SVGFEFuncBElement | SVGFEMergeNodeElement | SVGTextPathElement | SVGFEFloodElement | SVGMaskElement | SVGAElement | SVGSVGElement | SVGGElement | SVGFEDistantLightElement | SVGRadialGradientElement | SVGFilterElement | SVGPolygonElement | SVGFEGaussianBlurElement | HTMLSelectElement | HTMLLegendElement | HTMLElement | HTMLTableCaptionElement | HTMLTextAreaElement | HTMLModElement | HTMLHRElement | HTMLOutputElement | HTMLEmbedElement | HTMLCanvasElement | HTMLFrameSetElement | HTMLMarqueeElement | HTMLScriptElement | HTMLInputElement | HTMLMetaElement | HTMLStyleElement | HTMLObjectElement | HTMLTemplateElement | HTMLBRElement | HTMLAudioElement | HTMLIFrameElement | HTMLMapElement | HTMLTableElement | HTMLAnchorElement | HTMLMenuElement | HTMLPictureElement | HTMLParagraphElement | HTMLTableDataCellElement | HTMLTableSectionElement | HTMLQuoteElement | HTMLTableHeaderCellElement | HTMLProgressElement | HTMLLIElement | HTMLTableRowElement | HTMLFontElement | HTMLSpanElement | HTMLTableColElement | HTMLOptGroupElement | HTMLDataElement | HTMLDListElement | HTMLFieldSetElement | HTMLSourceElement | HTMLBodyElement | HTMLDirectoryElement | HTMLDivElement | HTMLUListElement | HTMLDetailsElement | HTMLHtmlElement | HTMLAreaElement | HTMLPreElement | HTMLMeterElement | HTMLAppletElement | HTMLFrameElement | HTMLOptionElement | HTMLImageElement | HTMLLinkElement | HTMLHeadingElement | HTMLSlotElement | HTMLVideoElement | HTMLBaseFontElement | HTMLTitleElement | HTMLButtonElement | HTMLHeadElement | HTMLDialogElement | HTMLParamElement | HTMLTrackElement | HTMLOListElement | HTMLDataListElement | HTMLLabelElement | HTMLFormElement | HTMLTimeElement | HTMLBaseElement}|{lang: undefined, domLang: Element | SVGSymbolElement | SVGMetadataElement | SVGUseElement | SVGFEImageElement | SVGPathElement | SVGViewElement | SVGFEConvolveMatrixElement | SVGFECompositeElement | SVGEllipseElement | SVGFEOffsetElement | SVGTextElement | SVGDefsElement | SVGFETurbulenceElement | SVGImageElement | SVGFEFuncGElement | SVGTSpanElement | SVGClipPathElement | SVGLinearGradientElement | SVGFEFuncRElement | SVGScriptElement | SVGFEColorMatrixElement | SVGFEComponentTransferElement | SVGStopElement | SVGMarkerElement | SVGFEMorphologyElement | SVGFEMergeElement | SVGFEPointLightElement | SVGForeignObjectElement | SVGFEDiffuseLightingElement | SVGStyleElement | SVGFEBlendElement | SVGCircleElement | SVGPolylineElement | SVGDescElement | SVGFESpecularLightingElement | SVGLineElement | SVGFESpotLightElement | SVGFETileElement | SVGPatternElement | SVGTitleElement | SVGSwitchElement | SVGRectElement | SVGFEDisplacementMapElement | SVGFEFuncAElement | SVGFEFuncBElement | SVGFEMergeNodeElement | SVGTextPathElement | SVGFEFloodElement | SVGMaskElement | SVGAElement | SVGSVGElement | SVGGElement | SVGFEDistantLightElement | SVGRadialGradientElement | SVGFilterElement | SVGPolygonElement | SVGFEGaussianBlurElement | HTMLSelectElement | HTMLLegendElement | HTMLElement | HTMLTableCaptionElement | HTMLTextAreaElement | HTMLModElement | HTMLHRElement | HTMLOutputElement | HTMLEmbedElement | HTMLCanvasElement | HTMLFrameSetElement | HTMLMarqueeElement | HTMLScriptElement | HTMLInputElement | HTMLMetaElement | HTMLStyleElement | HTMLObjectElement | HTMLTemplateElement | HTMLBRElement | HTMLAudioElement | HTMLIFrameElement | HTMLMapElement | HTMLTableElement | HTMLAnchorElement | HTMLMenuElement | HTMLPictureElement | HTMLParagraphElement | HTMLTableDataCellElement | HTMLTableSectionElement | HTMLQuoteElement | HTMLTableHeaderCellElement | HTMLProgressElement | HTMLLIElement | HTMLTableRowElement | HTMLFontElement | HTMLSpanElement | HTMLTableColElement | HTMLOptGroupElement | HTMLDataElement | HTMLDListElement | HTMLFieldSetElement | HTMLSourceElement | HTMLBodyElement | HTMLDirectoryElement | HTMLDivElement | HTMLUListElement | HTMLDetailsElement | HTMLHtmlElement | HTMLAreaElement | HTMLPreElement | HTMLMeterElement | HTMLAppletElement | HTMLFrameElement | HTMLOptionElement | HTMLImageElement | HTMLLinkElement | HTMLHeadingElement | HTMLSlotElement | HTMLVideoElement | HTMLBaseFontElement | HTMLTitleElement | HTMLButtonElement | HTMLHeadElement | HTMLDialogElement | HTMLParamElement | HTMLTrackElement | HTMLOListElement | HTMLDataListElement | HTMLLabelElement | HTMLFormElement | HTMLTimeElement | HTMLBaseElement}}
   */
  getLang = (doc) => {
    const domLang = doc.querySelector('*[lang]');
    if (domLang) {
      return {
        domLang,
        lang: domLang.getAttribute('lang') || '',
      };
    }

    return {
      domLang,
      lang: undefined,
    };
  };

  /**
   * @param {Document} doc
   * @returns {{preview: (string|*|undefined), domPreview: Element | SVGSymbolElement | SVGMetadataElement | SVGUseElement | SVGFEImageElement | SVGPathElement | SVGViewElement | SVGFEConvolveMatrixElement | SVGFECompositeElement | SVGEllipseElement | SVGFEOffsetElement | SVGTextElement | SVGDefsElement | SVGFETurbulenceElement | SVGImageElement | SVGFEFuncGElement | SVGTSpanElement | SVGClipPathElement | SVGLinearGradientElement | SVGFEFuncRElement | SVGScriptElement | SVGFEColorMatrixElement | SVGFEComponentTransferElement | SVGStopElement | SVGMarkerElement | SVGFEMorphologyElement | SVGFEMergeElement | SVGFEPointLightElement | SVGForeignObjectElement | SVGFEDiffuseLightingElement | SVGStyleElement | SVGFEBlendElement | SVGCircleElement | SVGPolylineElement | SVGDescElement | SVGFESpecularLightingElement | SVGLineElement | SVGFESpotLightElement | SVGFETileElement | SVGPatternElement | SVGTitleElement | SVGSwitchElement | SVGRectElement | SVGFEDisplacementMapElement | SVGFEFuncAElement | SVGFEFuncBElement | SVGFEMergeNodeElement | SVGTextPathElement | SVGFEFloodElement | SVGMaskElement | SVGAElement | SVGSVGElement | SVGGElement | SVGFEDistantLightElement | SVGRadialGradientElement | SVGFilterElement | SVGPolygonElement | SVGFEGaussianBlurElement | HTMLSelectElement | HTMLLegendElement | HTMLElement | HTMLTableCaptionElement | HTMLTextAreaElement | HTMLModElement | HTMLHRElement | HTMLOutputElement | HTMLEmbedElement | HTMLCanvasElement | HTMLFrameSetElement | HTMLMarqueeElement | HTMLScriptElement | HTMLInputElement | HTMLMetaElement | HTMLStyleElement | HTMLObjectElement | HTMLTemplateElement | HTMLBRElement | HTMLAudioElement | HTMLIFrameElement | HTMLMapElement | HTMLTableElement | HTMLAnchorElement | HTMLMenuElement | HTMLPictureElement | HTMLParagraphElement | HTMLTableDataCellElement | HTMLTableSectionElement | HTMLQuoteElement | HTMLTableHeaderCellElement | HTMLProgressElement | HTMLLIElement | HTMLTableRowElement | HTMLFontElement | HTMLSpanElement | HTMLTableColElement | HTMLOptGroupElement | HTMLDataElement | HTMLDListElement | HTMLFieldSetElement | HTMLSourceElement | HTMLBodyElement | HTMLDirectoryElement | HTMLDivElement | HTMLUListElement | HTMLDetailsElement | HTMLHtmlElement | HTMLAreaElement | HTMLPreElement | HTMLMeterElement | HTMLAppletElement | HTMLFrameElement | HTMLOptionElement | HTMLImageElement | HTMLLinkElement | HTMLHeadingElement | HTMLSlotElement | HTMLVideoElement | HTMLBaseFontElement | HTMLTitleElement | HTMLButtonElement | HTMLHeadElement | HTMLDialogElement | HTMLParamElement | HTMLTrackElement | HTMLOListElement | HTMLDataListElement | HTMLLabelElement | HTMLFormElement | HTMLTimeElement | HTMLBaseElement, title: (string|string), domTitle: Element | SVGTitleElement | HTMLTitleElement}}
   */
  getTitleAndPrefix = (doc) => {
    const title = doc.querySelector('title');
    let preview = doc.querySelector('.block-preview');
    if (!preview) {
      const elements = doc.querySelectorAll('*');
      for (let i = 0; i < elements.length; i++) {
        const style = elements[i].getAttribute('style');
        if (style && style.indexOf('-block-preview:') !== -1) {
          preview = elements[i];
          break;
        }
      }
    }

    if (!title) {
      const head = doc.querySelector('head');
      if (head) {
        const newTitle = doc.createElement('TITLE');
        newTitle.innerText = '';
        head.appendChild(newTitle);
      }
    }

    return {
      title:      title ? title.innerText : '',
      preview:    preview ? trim(preview.innerText) : undefined,
      domTitle:   title,
      domPreview: preview
    };
  }

  /**
   * @param {Document} doc
   * @returns {{domOGUrl: Element | SVGSymbolElement | SVGMetadataElement | SVGUseElement | SVGFEImageElement | SVGPathElement | SVGViewElement | SVGFEConvolveMatrixElement | SVGFECompositeElement | SVGEllipseElement | SVGFEOffsetElement | SVGTextElement | SVGDefsElement | SVGFETurbulenceElement | SVGImageElement | SVGFEFuncGElement | SVGTSpanElement | SVGClipPathElement | SVGLinearGradientElement | SVGFEFuncRElement | SVGScriptElement | SVGFEColorMatrixElement | SVGFEComponentTransferElement | SVGStopElement | SVGMarkerElement | SVGFEMorphologyElement | SVGFEMergeElement | SVGFEPointLightElement | SVGForeignObjectElement | SVGFEDiffuseLightingElement | SVGStyleElement | SVGFEBlendElement | SVGCircleElement | SVGPolylineElement | SVGDescElement | SVGFESpecularLightingElement | SVGLineElement | SVGFESpotLightElement | SVGFETileElement | SVGPatternElement | SVGTitleElement | SVGSwitchElement | SVGRectElement | SVGFEDisplacementMapElement | SVGFEFuncAElement | SVGFEFuncBElement | SVGFEMergeNodeElement | SVGTextPathElement | SVGFEFloodElement | SVGMaskElement | SVGAElement | SVGSVGElement | SVGGElement | SVGFEDistantLightElement | SVGRadialGradientElement | SVGFilterElement | SVGPolygonElement | SVGFEGaussianBlurElement | HTMLSelectElement | HTMLLegendElement | HTMLElement | HTMLTableCaptionElement | HTMLTextAreaElement | HTMLModElement | HTMLHRElement | HTMLOutputElement | HTMLEmbedElement | HTMLCanvasElement | HTMLFrameSetElement | HTMLMarqueeElement | HTMLScriptElement | HTMLInputElement | HTMLMetaElement | HTMLStyleElement | HTMLObjectElement | HTMLTemplateElement | HTMLBRElement | HTMLAudioElement | HTMLIFrameElement | HTMLMapElement | HTMLTableElement | HTMLAnchorElement | HTMLMenuElement | HTMLPictureElement | HTMLParagraphElement | HTMLTableDataCellElement | HTMLTableSectionElement | HTMLQuoteElement | HTMLTableHeaderCellElement | HTMLProgressElement | HTMLLIElement | HTMLTableRowElement | HTMLFontElement | HTMLSpanElement | HTMLTableColElement | HTMLOptGroupElement | HTMLDataElement | HTMLDListElement | HTMLFieldSetElement | HTMLSourceElement | HTMLBodyElement | HTMLDirectoryElement | HTMLDivElement | HTMLUListElement | HTMLDetailsElement | HTMLHtmlElement | HTMLAreaElement | HTMLPreElement | HTMLMeterElement | HTMLAppletElement | HTMLFrameElement | HTMLOptionElement | HTMLImageElement | HTMLLinkElement | HTMLHeadingElement | HTMLSlotElement | HTMLVideoElement | HTMLBaseFontElement | HTMLTitleElement | HTMLButtonElement | HTMLHeadElement | HTMLDialogElement | HTMLParamElement | HTMLTrackElement | HTMLOListElement | HTMLDataListElement | HTMLLabelElement | HTMLFormElement | HTMLTimeElement | HTMLBaseElement, ogTitle: (string|undefined), domOGImage: Element | SVGSymbolElement | SVGMetadataElement | SVGUseElement | SVGFEImageElement | SVGPathElement | SVGViewElement | SVGFEConvolveMatrixElement | SVGFECompositeElement | SVGEllipseElement | SVGFEOffsetElement | SVGTextElement | SVGDefsElement | SVGFETurbulenceElement | SVGImageElement | SVGFEFuncGElement | SVGTSpanElement | SVGClipPathElement | SVGLinearGradientElement | SVGFEFuncRElement | SVGScriptElement | SVGFEColorMatrixElement | SVGFEComponentTransferElement | SVGStopElement | SVGMarkerElement | SVGFEMorphologyElement | SVGFEMergeElement | SVGFEPointLightElement | SVGForeignObjectElement | SVGFEDiffuseLightingElement | SVGStyleElement | SVGFEBlendElement | SVGCircleElement | SVGPolylineElement | SVGDescElement | SVGFESpecularLightingElement | SVGLineElement | SVGFESpotLightElement | SVGFETileElement | SVGPatternElement | SVGTitleElement | SVGSwitchElement | SVGRectElement | SVGFEDisplacementMapElement | SVGFEFuncAElement | SVGFEFuncBElement | SVGFEMergeNodeElement | SVGTextPathElement | SVGFEFloodElement | SVGMaskElement | SVGAElement | SVGSVGElement | SVGGElement | SVGFEDistantLightElement | SVGRadialGradientElement | SVGFilterElement | SVGPolygonElement | SVGFEGaussianBlurElement | HTMLSelectElement | HTMLLegendElement | HTMLElement | HTMLTableCaptionElement | HTMLTextAreaElement | HTMLModElement | HTMLHRElement | HTMLOutputElement | HTMLEmbedElement | HTMLCanvasElement | HTMLFrameSetElement | HTMLMarqueeElement | HTMLScriptElement | HTMLInputElement | HTMLMetaElement | HTMLStyleElement | HTMLObjectElement | HTMLTemplateElement | HTMLBRElement | HTMLAudioElement | HTMLIFrameElement | HTMLMapElement | HTMLTableElement | HTMLAnchorElement | HTMLMenuElement | HTMLPictureElement | HTMLParagraphElement | HTMLTableDataCellElement | HTMLTableSectionElement | HTMLQuoteElement | HTMLTableHeaderCellElement | HTMLProgressElement | HTMLLIElement | HTMLTableRowElement | HTMLFontElement | HTMLSpanElement | HTMLTableColElement | HTMLOptGroupElement | HTMLDataElement | HTMLDListElement | HTMLFieldSetElement | HTMLSourceElement | HTMLBodyElement | HTMLDirectoryElement | HTMLDivElement | HTMLUListElement | HTMLDetailsElement | HTMLHtmlElement | HTMLAreaElement | HTMLPreElement | HTMLMeterElement | HTMLAppletElement | HTMLFrameElement | HTMLOptionElement | HTMLImageElement | HTMLLinkElement | HTMLHeadingElement | HTMLSlotElement | HTMLVideoElement | HTMLBaseFontElement | HTMLTitleElement | HTMLButtonElement | HTMLHeadElement | HTMLDialogElement | HTMLParamElement | HTMLTrackElement | HTMLOListElement | HTMLDataListElement | HTMLLabelElement | HTMLFormElement | HTMLTimeElement | HTMLBaseElement, description: (string|undefined), ogUrl: (string|undefined), appTitle: (string|undefined), ogDescription: (string|undefined), domOGDescription: Element | SVGSymbolElement | SVGMetadataElement | SVGUseElement | SVGFEImageElement | SVGPathElement | SVGViewElement | SVGFEConvolveMatrixElement | SVGFECompositeElement | SVGEllipseElement | SVGFEOffsetElement | SVGTextElement | SVGDefsElement | SVGFETurbulenceElement | SVGImageElement | SVGFEFuncGElement | SVGTSpanElement | SVGClipPathElement | SVGLinearGradientElement | SVGFEFuncRElement | SVGScriptElement | SVGFEColorMatrixElement | SVGFEComponentTransferElement | SVGStopElement | SVGMarkerElement | SVGFEMorphologyElement | SVGFEMergeElement | SVGFEPointLightElement | SVGForeignObjectElement | SVGFEDiffuseLightingElement | SVGStyleElement | SVGFEBlendElement | SVGCircleElement | SVGPolylineElement | SVGDescElement | SVGFESpecularLightingElement | SVGLineElement | SVGFESpotLightElement | SVGFETileElement | SVGPatternElement | SVGTitleElement | SVGSwitchElement | SVGRectElement | SVGFEDisplacementMapElement | SVGFEFuncAElement | SVGFEFuncBElement | SVGFEMergeNodeElement | SVGTextPathElement | SVGFEFloodElement | SVGMaskElement | SVGAElement | SVGSVGElement | SVGGElement | SVGFEDistantLightElement | SVGRadialGradientElement | SVGFilterElement | SVGPolygonElement | SVGFEGaussianBlurElement | HTMLSelectElement | HTMLLegendElement | HTMLElement | HTMLTableCaptionElement | HTMLTextAreaElement | HTMLModElement | HTMLHRElement | HTMLOutputElement | HTMLEmbedElement | HTMLCanvasElement | HTMLFrameSetElement | HTMLMarqueeElement | HTMLScriptElement | HTMLInputElement | HTMLMetaElement | HTMLStyleElement | HTMLObjectElement | HTMLTemplateElement | HTMLBRElement | HTMLAudioElement | HTMLIFrameElement | HTMLMapElement | HTMLTableElement | HTMLAnchorElement | HTMLMenuElement | HTMLPictureElement | HTMLParagraphElement | HTMLTableDataCellElement | HTMLTableSectionElement | HTMLQuoteElement | HTMLTableHeaderCellElement | HTMLProgressElement | HTMLLIElement | HTMLTableRowElement | HTMLFontElement | HTMLSpanElement | HTMLTableColElement | HTMLOptGroupElement | HTMLDataElement | HTMLDListElement | HTMLFieldSetElement | HTMLSourceElement | HTMLBodyElement | HTMLDirectoryElement | HTMLDivElement | HTMLUListElement | HTMLDetailsElement | HTMLHtmlElement | HTMLAreaElement | HTMLPreElement | HTMLMeterElement | HTMLAppletElement | HTMLFrameElement | HTMLOptionElement | HTMLImageElement | HTMLLinkElement | HTMLHeadingElement | HTMLSlotElement | HTMLVideoElement | HTMLBaseFontElement | HTMLTitleElement | HTMLButtonElement | HTMLHeadElement | HTMLDialogElement | HTMLParamElement | HTMLTrackElement | HTMLOListElement | HTMLDataListElement | HTMLLabelElement | HTMLFormElement | HTMLTimeElement | HTMLBaseElement, ogImage: (string|undefined), domAppTitle: Element | SVGSymbolElement | SVGMetadataElement | SVGUseElement | SVGFEImageElement | SVGPathElement | SVGViewElement | SVGFEConvolveMatrixElement | SVGFECompositeElement | SVGEllipseElement | SVGFEOffsetElement | SVGTextElement | SVGDefsElement | SVGFETurbulenceElement | SVGImageElement | SVGFEFuncGElement | SVGTSpanElement | SVGClipPathElement | SVGLinearGradientElement | SVGFEFuncRElement | SVGScriptElement | SVGFEColorMatrixElement | SVGFEComponentTransferElement | SVGStopElement | SVGMarkerElement | SVGFEMorphologyElement | SVGFEMergeElement | SVGFEPointLightElement | SVGForeignObjectElement | SVGFEDiffuseLightingElement | SVGStyleElement | SVGFEBlendElement | SVGCircleElement | SVGPolylineElement | SVGDescElement | SVGFESpecularLightingElement | SVGLineElement | SVGFESpotLightElement | SVGFETileElement | SVGPatternElement | SVGTitleElement | SVGSwitchElement | SVGRectElement | SVGFEDisplacementMapElement | SVGFEFuncAElement | SVGFEFuncBElement | SVGFEMergeNodeElement | SVGTextPathElement | SVGFEFloodElement | SVGMaskElement | SVGAElement | SVGSVGElement | SVGGElement | SVGFEDistantLightElement | SVGRadialGradientElement | SVGFilterElement | SVGPolygonElement | SVGFEGaussianBlurElement | HTMLSelectElement | HTMLLegendElement | HTMLElement | HTMLTableCaptionElement | HTMLTextAreaElement | HTMLModElement | HTMLHRElement | HTMLOutputElement | HTMLEmbedElement | HTMLCanvasElement | HTMLFrameSetElement | HTMLMarqueeElement | HTMLScriptElement | HTMLInputElement | HTMLMetaElement | HTMLStyleElement | HTMLObjectElement | HTMLTemplateElement | HTMLBRElement | HTMLAudioElement | HTMLIFrameElement | HTMLMapElement | HTMLTableElement | HTMLAnchorElement | HTMLMenuElement | HTMLPictureElement | HTMLParagraphElement | HTMLTableDataCellElement | HTMLTableSectionElement | HTMLQuoteElement | HTMLTableHeaderCellElement | HTMLProgressElement | HTMLLIElement | HTMLTableRowElement | HTMLFontElement | HTMLSpanElement | HTMLTableColElement | HTMLOptGroupElement | HTMLDataElement | HTMLDListElement | HTMLFieldSetElement | HTMLSourceElement | HTMLBodyElement | HTMLDirectoryElement | HTMLDivElement | HTMLUListElement | HTMLDetailsElement | HTMLHtmlElement | HTMLAreaElement | HTMLPreElement | HTMLMeterElement | HTMLAppletElement | HTMLFrameElement | HTMLOptionElement | HTMLImageElement | HTMLLinkElement | HTMLHeadingElement | HTMLSlotElement | HTMLVideoElement | HTMLBaseFontElement | HTMLTitleElement | HTMLButtonElement | HTMLHeadElement | HTMLDialogElement | HTMLParamElement | HTMLTrackElement | HTMLOListElement | HTMLDataListElement | HTMLLabelElement | HTMLFormElement | HTMLTimeElement | HTMLBaseElement, domDescription: Element | SVGSymbolElement | SVGMetadataElement | SVGUseElement | SVGFEImageElement | SVGPathElement | SVGViewElement | SVGFEConvolveMatrixElement | SVGFECompositeElement | SVGEllipseElement | SVGFEOffsetElement | SVGTextElement | SVGDefsElement | SVGFETurbulenceElement | SVGImageElement | SVGFEFuncGElement | SVGTSpanElement | SVGClipPathElement | SVGLinearGradientElement | SVGFEFuncRElement | SVGScriptElement | SVGFEColorMatrixElement | SVGFEComponentTransferElement | SVGStopElement | SVGMarkerElement | SVGFEMorphologyElement | SVGFEMergeElement | SVGFEPointLightElement | SVGForeignObjectElement | SVGFEDiffuseLightingElement | SVGStyleElement | SVGFEBlendElement | SVGCircleElement | SVGPolylineElement | SVGDescElement | SVGFESpecularLightingElement | SVGLineElement | SVGFESpotLightElement | SVGFETileElement | SVGPatternElement | SVGTitleElement | SVGSwitchElement | SVGRectElement | SVGFEDisplacementMapElement | SVGFEFuncAElement | SVGFEFuncBElement | SVGFEMergeNodeElement | SVGTextPathElement | SVGFEFloodElement | SVGMaskElement | SVGAElement | SVGSVGElement | SVGGElement | SVGFEDistantLightElement | SVGRadialGradientElement | SVGFilterElement | SVGPolygonElement | SVGFEGaussianBlurElement | HTMLSelectElement | HTMLLegendElement | HTMLElement | HTMLTableCaptionElement | HTMLTextAreaElement | HTMLModElement | HTMLHRElement | HTMLOutputElement | HTMLEmbedElement | HTMLCanvasElement | HTMLFrameSetElement | HTMLMarqueeElement | HTMLScriptElement | HTMLInputElement | HTMLMetaElement | HTMLStyleElement | HTMLObjectElement | HTMLTemplateElement | HTMLBRElement | HTMLAudioElement | HTMLIFrameElement | HTMLMapElement | HTMLTableElement | HTMLAnchorElement | HTMLMenuElement | HTMLPictureElement | HTMLParagraphElement | HTMLTableDataCellElement | HTMLTableSectionElement | HTMLQuoteElement | HTMLTableHeaderCellElement | HTMLProgressElement | HTMLLIElement | HTMLTableRowElement | HTMLFontElement | HTMLSpanElement | HTMLTableColElement | HTMLOptGroupElement | HTMLDataElement | HTMLDListElement | HTMLFieldSetElement | HTMLSourceElement | HTMLBodyElement | HTMLDirectoryElement | HTMLDivElement | HTMLUListElement | HTMLDetailsElement | HTMLHtmlElement | HTMLAreaElement | HTMLPreElement | HTMLMeterElement | HTMLAppletElement | HTMLFrameElement | HTMLOptionElement | HTMLImageElement | HTMLLinkElement | HTMLHeadingElement | HTMLSlotElement | HTMLVideoElement | HTMLBaseFontElement | HTMLTitleElement | HTMLButtonElement | HTMLHeadElement | HTMLDialogElement | HTMLParamElement | HTMLTrackElement | HTMLOListElement | HTMLDataListElement | HTMLLabelElement | HTMLFormElement | HTMLTimeElement | HTMLBaseElement, domOGTitle: Element | SVGSymbolElement | SVGMetadataElement | SVGUseElement | SVGFEImageElement | SVGPathElement | SVGViewElement | SVGFEConvolveMatrixElement | SVGFECompositeElement | SVGEllipseElement | SVGFEOffsetElement | SVGTextElement | SVGDefsElement | SVGFETurbulenceElement | SVGImageElement | SVGFEFuncGElement | SVGTSpanElement | SVGClipPathElement | SVGLinearGradientElement | SVGFEFuncRElement | SVGScriptElement | SVGFEColorMatrixElement | SVGFEComponentTransferElement | SVGStopElement | SVGMarkerElement | SVGFEMorphologyElement | SVGFEMergeElement | SVGFEPointLightElement | SVGForeignObjectElement | SVGFEDiffuseLightingElement | SVGStyleElement | SVGFEBlendElement | SVGCircleElement | SVGPolylineElement | SVGDescElement | SVGFESpecularLightingElement | SVGLineElement | SVGFESpotLightElement | SVGFETileElement | SVGPatternElement | SVGTitleElement | SVGSwitchElement | SVGRectElement | SVGFEDisplacementMapElement | SVGFEFuncAElement | SVGFEFuncBElement | SVGFEMergeNodeElement | SVGTextPathElement | SVGFEFloodElement | SVGMaskElement | SVGAElement | SVGSVGElement | SVGGElement | SVGFEDistantLightElement | SVGRadialGradientElement | SVGFilterElement | SVGPolygonElement | SVGFEGaussianBlurElement | HTMLSelectElement | HTMLLegendElement | HTMLElement | HTMLTableCaptionElement | HTMLTextAreaElement | HTMLModElement | HTMLHRElement | HTMLOutputElement | HTMLEmbedElement | HTMLCanvasElement | HTMLFrameSetElement | HTMLMarqueeElement | HTMLScriptElement | HTMLInputElement | HTMLMetaElement | HTMLStyleElement | HTMLObjectElement | HTMLTemplateElement | HTMLBRElement | HTMLAudioElement | HTMLIFrameElement | HTMLMapElement | HTMLTableElement | HTMLAnchorElement | HTMLMenuElement | HTMLPictureElement | HTMLParagraphElement | HTMLTableDataCellElement | HTMLTableSectionElement | HTMLQuoteElement | HTMLTableHeaderCellElement | HTMLProgressElement | HTMLLIElement | HTMLTableRowElement | HTMLFontElement | HTMLSpanElement | HTMLTableColElement | HTMLOptGroupElement | HTMLDataElement | HTMLDListElement | HTMLFieldSetElement | HTMLSourceElement | HTMLBodyElement | HTMLDirectoryElement | HTMLDivElement | HTMLUListElement | HTMLDetailsElement | HTMLHtmlElement | HTMLAreaElement | HTMLPreElement | HTMLMeterElement | HTMLAppletElement | HTMLFrameElement | HTMLOptionElement | HTMLImageElement | HTMLLinkElement | HTMLHeadingElement | HTMLSlotElement | HTMLVideoElement | HTMLBaseFontElement | HTMLTitleElement | HTMLButtonElement | HTMLHeadElement | HTMLDialogElement | HTMLParamElement | HTMLTrackElement | HTMLOListElement | HTMLDataListElement | HTMLLabelElement | HTMLFormElement | HTMLTimeElement | HTMLBaseElement}}
   */
  getMetaTags = (doc) => {
    const domDescription = doc.querySelector('meta[name="description"]');
    const domAppTitle = doc.querySelector('meta[name="apple-mobile-web-app-title"]');
    const domOGTitle = doc.querySelector('meta[property="og:title"]');
    const domOGDescription = doc.querySelector('meta[property="og:description"]');
    const domOGImage = doc.querySelector('meta[property="og:image"]');
    const domOGUrl = doc.querySelector('meta[property="og:url"]');

    return {
      domDescription,
      domAppTitle,
      domOGTitle,
      domOGDescription,
      domOGImage,
      domOGUrl,
      description:   domDescription ? (domDescription.getAttribute('content') || '') : undefined,
      appTitle:      domAppTitle ? (domAppTitle.getAttribute('content') || '') : undefined,
      ogTitle:       domOGTitle ? (domOGTitle.getAttribute('content') || '') : undefined,
      ogDescription: domOGDescription ? (domOGDescription.getAttribute('content') || '') : undefined,
      ogImage:       domOGImage ? (domOGImage.getAttribute('content') || '') : undefined,
      ogUrl:         domOGUrl ? (domOGUrl.getAttribute('content') || '') : undefined,
    };
  };
}

export default new DocMeta();