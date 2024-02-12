import React, { useEffect, useState, useRef } from 'react';
import { useSelector } from 'react-redux';
import { Scrollbars } from 'react-custom-scrollbars';
import Mask from 'components/Mask';
import MaskChild from 'components/MaskChild';
import Icon from 'components/Icon';
import {
  Container,
  Spinner,
  Percent,
  ErrorsContainer,
  ErrorHandle,
  ErrorsWrap,
  ErrorsList
} from './styles';

const UploadingStatus = () => {
  const [percent, setPercent] = useState(0);
  const [errorsOpen, setErrorsOpen] = useState(false);
  const status = useSelector(state => state.builder.uploadingStatus);
  const scrollbarsRef = useRef();
  const prevPercentRef = useRef(0);
  const t1Ref = useRef(0);
  const t2Ref = useRef(0);
  const errorsLenRef = useRef(0);

  /**
   *
   */
  // eslint-disable-next-line consistent-return
  useEffect(() => {
    if (status) {
      if (status.errors && status.errors.length > 0) {
        if (status.errors.length > errorsLenRef.current) {
          setTimeout(() => {
            scrollbarsRef.current.scrollToBottom();
          }, 100);
        }
        errorsLenRef.current = status.errors.length;
      }

      if (status.percent >= 90) {
        setPercent(status.percent);
        prevPercentRef.current = status.percent;
        clearTimeout(t1Ref.current);
        return;
      }

      if (status.percent > prevPercentRef.current) {
        clearTimeout(t1Ref.current);
        let diff = status.percent - prevPercentRef.current;
        const incr = () => {
          setPercent((v) => {
            if (v < 90) {
              return v + 1;
            }
            return v;
          });
          diff -= 1;
          if (diff > 0) {
            t1Ref.current = setTimeout(incr, 30);
          }
        };
        t1Ref.current = setTimeout(incr, 30);
      } else {
        t2Ref.current = setTimeout(() => {
          setPercent((v) => {
            if (v < 90) {
              return v + 1;
            }
            return v;
          });
        }, 5000);
      }

      prevPercentRef.current = status.percent;
      return;
    }

    setPercent(0);
    errorsLenRef.current = 0;
    prevPercentRef.current = 0;
    clearTimeout(t1Ref.current);
    clearTimeout(t2Ref.current);
  }, [status]);

  return (
    <Mask open={status !== null}>
      <MaskChild animation="zoomIn">
        <Container>
          <h3 className="mb-2">
            Template is uploading and its components are being generated.
          </h3>
          <div className="position-relative mt-3">
            <Spinner className="fancybox-loading fancybox-loading-inline" />
            <Percent>
              {percent}%
            </Percent>
          </div>
          {status && status.message && (
            <small className="d-block mt-2">
              {status.message}
            </small>
          )}
          {status && status.errors && status.errors.length > 0 && (
            <ErrorsContainer className="mt-3">
              <ErrorHandle onClick={() => setErrorsOpen(!errorsOpen)}>
                Errors ({status.errors.length})
                <Icon
                  className="ml-1"
                  name={errorsOpen ? 'be-symbol-arrow-down' : 'be-symbol-arrow-right'}
                />
              </ErrorHandle>
              <ErrorsWrap open={errorsOpen}>
                <Scrollbars
                  ref={scrollbarsRef}
                  autoHeightMax={200}
                  autoHeight
                >
                  <ErrorsList>
                    {status.errors.map((err, i) => (
                      <li key={i}>
                        {i + 1}. Server responded with status {err.status}.<br />{err.url}
                      </li>
                    ))}
                  </ErrorsList>
                </Scrollbars>
              </ErrorsWrap>
            </ErrorsContainer>
          )}
        </Container>
      </MaskChild>
    </Mask>
  );
};

export default UploadingStatus;
