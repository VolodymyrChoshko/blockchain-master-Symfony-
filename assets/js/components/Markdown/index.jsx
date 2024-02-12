import React from 'react';
import PropTypes from 'prop-types';
import ReactMarkdown from 'react-markdown';
import remarkGfm from 'remark-gfm';
import remarkEmoji from 'remark-emoji';
import rehypeRaw from 'rehype-raw';

const Markdown = ({ markdown }) => {
  return (
    <ReactMarkdown
      rehypePlugins={[rehypeRaw]}
      remarkPlugins={[remarkGfm, remarkEmoji]}
      linkTarget="_blank"
    >
      {markdown.replace(/\n/g, '\n\n')}
    </ReactMarkdown>
  );
};

Markdown.propTypes = {
  markdown: PropTypes.string.isRequired,
};

export default Markdown;
