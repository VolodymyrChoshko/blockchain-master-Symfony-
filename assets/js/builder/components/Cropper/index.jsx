import React from 'react';
import PropTypes from 'prop-types';
import { default as ReactCropper } from 'react-cropper'; // eslint-disable-line
import { connect } from 'react-redux';
import browser from 'utils/browser';
import { mediaActions } from 'builder/actions';
import { loading, mapDispatchToProps } from 'utils';
import { Button, ButtonGroup, Mask, MaskChild } from 'components';

const mapStateToProps = state => ({
  isCropping: state.media.isCropping,
  cropData:   state.media.cropData
});

@connect(
  mapStateToProps,
  mapDispatchToProps(mediaActions)
)
export default class Cropper extends React.PureComponent {
  static propTypes = {
    isCropping:  PropTypes.bool.isRequired,
    cropData:    PropTypes.object,
    mediaDelete: PropTypes.func.isRequired
  };

  static defaultProps = {};

  /**
   * @param {*} props
   */
  constructor(props) {
    super(props);

    this.cropper = React.createRef();
    this.values  = {};

    this.state = {
      minCropBoxWidth:  undefined,
      minCropBoxHeight: undefined,
      aspectRatio:      undefined,
      isReady:          false
    };
  }

  /**
   * @param {*} prevProps
   */
  componentDidUpdate(prevProps) {
    const { isCropping, cropData } = this.props;

    if (isCropping && !prevProps.isCropping) {
      const { cropWidth, cropHeight, block, src } = cropData;
      loading(true);

      const i = new Image();
      i.onload = () => {
        setTimeout(() => {
          loading(false);
        }, 1000);

        this.setState({ isReady: true });
      };
      i.src = src;

      const newState = {
        minCropBoxWidth:  undefined,
        minCropBoxHeight: undefined,
        aspectRatio:      undefined,
      };

      if (!block.rules.isAutoWidth) {
        newState.minCropBoxWidth = cropWidth;
      }
      if (!block.rules.isAutoHeight) {
        newState.minCropBoxHeight = cropHeight;
      }
      if (!(block.rules.isAutoWidth || block.rules.isAutoHeight)) {
        newState.aspectRatio = cropWidth / cropHeight;
      }

      this.setState(newState);
    }
  }

  /**
   *
   */
  handleCropClick = () => {
    const { cropData } = this.props;
    const { onCrop } = cropData;

    this.setState({ isReady: false });
    if (onCrop) {
      const { cropWidth, cropHeight, block } = cropData;
      onCrop(
        this.values,
        block.rules.isAutoWidth,
        block.rules.isAutoHeight,
        cropWidth,
        cropHeight
      );
    }
  };

  /**
   *
   */
  handleCancelClick = () => {
    const { cropData, mediaDelete } = this.props;
    const { onCancel } = cropData;

    mediaDelete(cropData.id);

    this.setState({ isReady: false });
    if (onCancel) {
      onCancel();
    }
  };

  /**
   *
   */
  handleSkipClick = () => {
    const { cropData } = this.props;
    const { onCrop } = cropData;

    this.setState({ isReady: false });
    if (onCrop) {
      const { cropWidth, cropHeight, block } = cropData;

      onCrop(
        null,
        block.rules.isAutoWidth,
        block.rules.isAutoHeight,
        cropWidth,
        cropHeight
      );
    }
  };

  /**
   * @returns {*}
   */
  renderCropper = () => {
    const { cropData } = this.props;
    const { minCropBoxWidth, minCropBoxHeight, aspectRatio, isReady } = this.state;

    const { block, src } = cropData;
    let { width, height } = browser.getViewpointSize();
    if (cropData.width < width && cropData.height < height) {
      ({ width, height } = cropData);
    } else {
      width  -= 100;
      height -= 100;
    }

    return (
      <div className="modal modal-auto-height modal-auto-width visible d-flex flex-column">
        {isReady && (
          <ReactCropper
            viewMode={1}
            zoomable={false}
            ref={this.cropper}
            src={cropData.src}
            background={false}
            style={{ width, height }}
            aspectRatio={aspectRatio}
            crop={e => this.values = e.detail}
            minCropBoxWidth={minCropBoxWidth}
            minCropBoxHeight={minCropBoxHeight}
          />
        )}
        <div className="text-center pt-4 mb-4">
          <ButtonGroup>
            <Button variant="main" onClick={this.handleCropClick}>
              Crop
            </Button>
            {(block.rules.isAutoHeight || src.toLowerCase().indexOf('.png') !== -1) && (
              <Button variant="main" onClick={this.handleSkipClick}>
                Skip
              </Button>
            )}
            <Button variant="alt" onClick={this.handleCancelClick}>
              Cancel
            </Button>
          </ButtonGroup>
        </div>
      </div>
    );
  };

  /**
   * @returns {*}
   */
  render() {
    const { isCropping } = this.props;
    const { isReady } = this.state;

    return (
      <Mask open={isReady} onVisible={this.handleMaskVisible}>
        <MaskChild animation="zoomIn">
          {isCropping && this.renderCropper()}
        </MaskChild>
      </Mask>
    );
  }
}
