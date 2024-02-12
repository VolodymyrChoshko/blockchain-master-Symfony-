import React from 'react';
import { useSelector } from 'react-redux';
import Mask from 'components/Mask';
import MaskChild from 'components/MaskChild';
import {
  Container,
  Spinner,
} from './styles';

const UpgradingStatus = () => {
  const upgrading = useSelector(state => state.builder.upgrading);

  let message = 'Layouts are being updated.';
  if (upgrading.indexOf('pins') !== -1) {
    message = 'Pins are being updated.';
  } else if (upgrading.indexOf('email') !== -1) {
    message = 'Email is being updated.';
  }

  return (
    <Mask open={upgrading.length > 0}>
      <MaskChild animation="zoomIn">
        <Container>
          <h3 className="mb-2">
            {message}
          </h3>
          <div className="position-relative mt-3">
            <Spinner className="fancybox-loading fancybox-loading-inline" />
          </div>
          <small className="d-block mt-2">
            Please wait until the update is complete.
          </small>
        </Container>
      </MaskChild>
    </Mask>
  );
};

export default UpgradingStatus;
