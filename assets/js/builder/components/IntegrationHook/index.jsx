import React, { useEffect, useState, useRef } from 'react';
import PropTypes from 'prop-types';
import { connect } from 'react-redux';
import { Loading } from 'components';
import { hasIntegrationHook, dispatchHook } from 'lib/integrations';

const IntegrationHook = ({ id, hook, source, sources, onComplete }) => {
  const [isLoading, setLoading]   = useState(false);
  const [hasHook, setHasHook]     = useState(false);
  const [responses, setResponses] = useState([]);
  const unmountingRef = useRef(false);
  const formRef = useRef();

  /**
   *
   */
  useEffect(() => {
    setResponses([]);
    setLoading(false);

    const _hasHook = hasIntegrationHook(source || sources, hook);
    setHasHook(_hasHook);
    if (_hasHook) {
      if (!unmountingRef.current) {
        setLoading(true);
        dispatchHook(hook, id, source || null)
          .then((resp) => {
            if (!unmountingRef.current) {
              setResponses(resp);
            }
          })
          .finally(() => {
            if (!unmountingRef.current) {
              setLoading(false);
              if (onComplete) {
                onComplete(formRef.current);
              }
            }
          });
      }
    }

    return () => unmountingRef.current = true;
  }, [hook]);

  if (!hasHook) {
    return null;
  }
  if (isLoading) {
    return <Loading ellipsis />;
  }

  return (
    <div ref={formRef}>
      {responses.map((__html, i) => (
        <div key={i} dangerouslySetInnerHTML={{ __html }} />
      ))}
    </div>
  );
};

IntegrationHook.propTypes = {
  id:         PropTypes.number.isRequired,
  hook:       PropTypes.string.isRequired,
  source:     PropTypes.object,
  sources:    PropTypes.array.isRequired,
  onComplete: PropTypes.func,
};

const mapStateToProps = state => ({
  id:      state.builder.id,
  sources: state.source.sources
});

export default connect(mapStateToProps)(IntegrationHook);
