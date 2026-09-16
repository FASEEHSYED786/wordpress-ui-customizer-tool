(function (wp) {
  if (!wp || !wp.blocks) {
    return;
  }

  var registerBlockType = wp.blocks.registerBlockType;
  var el = wp.element.createElement;
  var SelectControl = wp.components.SelectControl;
  var InspectorControls = wp.blockEditor.InspectorControls;
  var PanelBody = wp.components.PanelBody;
  var snippets = (window.vssBlock && window.vssBlock.snippets) ? window.vssBlock.snippets : [];

  var options = [{ label: 'Select a snippet', value: '' }].concat(
    snippets.map(function (snippet) {
      return { label: snippet.title + ' (' + snippet.slug + ')', value: snippet.slug };
    })
  );

  registerBlockType('vss/content', {
    apiVersion: 2,
    title: 'Studio Dynamic Content',
    description: 'Insert a Visual Site Studio snippet with schedule and visibility rules.',
    icon: 'schedule',
    category: 'widgets',
    attributes: {
      slug: { type: 'string', default: '' }
    },
    edit: function (props) {
      var slug = props.attributes.slug;
      var chosen = snippets.find(function (snippet) { return snippet.slug === slug; });
      return el(
        'div',
        { className: 'vss-block-preview' },
        el(InspectorControls, {},
          el(PanelBody, { title: 'Snippet' },
            el(SelectControl, {
              label: 'Dynamic content',
              value: slug,
              options: options,
              onChange: function (value) { props.setAttributes({ slug: value }); }
            })
          )
        ),
        el('p', {}, chosen ? chosen.title : 'Choose a Visual Site Studio snippet in the sidebar.')
      );
    },
    save: function () {
      return null;
    }
  });
})(window.wp);
