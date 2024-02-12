import React from 'react';
import PropTypes from 'prop-types';
import { Container, Head, Section, Spacer } from './styles';

const Box = ({
  id,
  wide,
  borderTheme,
  narrow,
  padded,
  fluid,
  white,
  shadow,
  className,
  style,
  overflowHidden,
  children,
  onMouseLeave
}) => {
  return (
    <Container
      id={id}
      borderTheme={borderTheme}
      className={`be-box ${className}`}
      style={style}
      wide={wide}
      narrow={narrow}
      fluid={fluid}
      white={white}
      shadow={shadow}
      padded={padded}
      overflowHidden={overflowHidden}
      onMouseLeave={onMouseLeave}
    >
      {children}
    </Container>
  );
};

Box.propTypes = {
  id:             PropTypes.string,
  wide:           PropTypes.bool,
  borderTheme:    PropTypes.oneOf(['none', 'success', 'error']),
  narrow:         PropTypes.bool,
  fluid:          PropTypes.bool,
  white:          PropTypes.bool,
  shadow:         PropTypes.bool,
  style:          PropTypes.object,
  className:      PropTypes.string,
  children:       PropTypes.node,
  overflowHidden: PropTypes.bool,
  padded:         PropTypes.bool,
  onMouseLeave:   PropTypes.func
};

Box.defaultProps = {
  id:             '',
  borderTheme:    'none',
  wide:           false,
  narrow:         false,
  fluid:          false,
  white:          false,
  shadow:         true,
  style:          {},
  className:      '',
  overflowHidden: true,
  padded:         true
};

Box.Head = Head;
Box.Section = Section;
Box.Spacer = Spacer;

export default Box;
