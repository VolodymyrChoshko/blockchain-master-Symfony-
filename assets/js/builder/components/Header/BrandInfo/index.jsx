import React, { useEffect, useRef } from 'react';
import { useSelector } from 'react-redux';
import { multilineEllipsis } from 'utils/browser';

const BrandInfo = () => {
  const title = useSelector(state => state.builder.title);
  const tid = useSelector(state => state.builder.tid);
  const id = useSelector(state => state.builder.id);
  const titleRef = useRef();

  /**
   *
   */
  useEffect(() => {
    if (titleRef.current && title) {
      multilineEllipsis(titleRef.current);
    }
  }, [titleRef, title]);

  return (
    <div className="builder-header-brand-info flex-grow-1 flex-basis-0 flex-no-wrap">
      <a className="builder-header-brand" href={`/t/${tid || id}`}>
        <img src="https://app.blocksedit.com/assets/Blocks-Edit-Symbol.svg" alt="Blocks Edit" />
      </a>
      <div className="builder-header-info">
        <a href={`/t/${tid || id}`}>
          ← Back to Dashboard
        </a>
        <div ref={titleRef} className="builder-header-title" style={{ width: '100%' }}>
          {title}
        </div>
      </div>
    </div>
  );
};

export default BrandInfo;
