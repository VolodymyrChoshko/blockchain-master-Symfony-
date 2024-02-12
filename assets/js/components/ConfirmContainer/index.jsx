import React, { useEffect } from 'react';
import { useSelector } from 'react-redux';
import { useUIActions } from 'builder/actions/uiActions';
import Confirm from 'components/Confirm';

const ConfirmContainer = () => {
  const uiActions = useUIActions();
  const confirms = useSelector(state => state.ui.confirms);

  /**
   *
   */
  useEffect(() => {
    window.jConfirm = uiActions.confirm;
    window.jLoading = uiActions.confirmLoading;
    window.jNotice  = uiActions.notice;
    window.jAlert   = uiActions.alert;
  }, []);

  return confirms.map(({ title, content, options, buttons, isNotice }, i) => (
    <Confirm key={i} index={i} title={title} buttons={buttons} options={options} notice={isNotice} open>
      {content}
    </Confirm>
  ));
};

export default ConfirmContainer;
