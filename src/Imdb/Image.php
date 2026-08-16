<?php

/**
 * imdbGraphQLPHP
 * This program is free software; you can redistribute and/or modify it
 * under the terms of the GNU General Public License (see doc/LICENSE)
 */

declare(strict_types=1);

namespace Imdb;

/**
 * Image processing functions to calculate image url parameters to get thumbnails just like imdb uses
 */
class Image
{
    /**
     * Calculate The total result parameter and determine if SX or SY is used
     * @param int $fullImageWidth the width in pixels of the large original image
     * @param int $fullImageHeight the height in pixels of the large original image
     * @param int $newImageWidth the width in pixels of the desired cropt/resized thumb image
     * @param int $newImageHeight the height in pixels of the desired cropt/resized thumb image
     * @return string example 'QL100_SX190_CR0,15,190,281_.jpg'
     * QL100 = Quality Level, 100 the highest, 0 the lowest quality
     * SX190 = S (scale) X190 desired width
     * CR = Crop (crop left and right, crop top and bottom, New width, New Height)
     * @see IMDB page / (TitlePage)
     */
    public function resultParameter(int $fullImageWidth, int $fullImageHeight, int $newImageWidth, int $newImageHeight): string
    {
        // original source aspect ratio
        $ratio_orig = $fullImageWidth / $fullImageHeight;

        // new aspect ratio
        $ratio_new = $newImageWidth / $newImageHeight;

        // check if the image must be treated as SX or SY
        if ($ratio_new < $ratio_orig) {
            $cropParameter = $this->thumbUrlCropParameter($fullImageWidth, $fullImageHeight, $newImageWidth, $newImageHeight);
            return 'QL75_UY' . $newImageHeight . '_CR' . $cropParameter . ',0,' . $newImageWidth . ',' . $newImageHeight . '_.jpg';
        } else {
            $cropParameter = $this->thumbUrlCropParameterVertical($fullImageWidth, $fullImageHeight, $newImageWidth, $newImageHeight);
            return 'QL75_UX' . $newImageWidth . '_CR0,' . $cropParameter . ',' . $newImageWidth . ',' . $newImageHeight . '_.jpg';
        }
    }

    /**
     * Calculate if cropValue has to be round to previous or next even integer
     * @param float $totalPixelCropSize how much pixels in total need to be cropped
     */
    private function roundInteger(float $totalPixelCropSize): float
    {
        if ((($totalPixelCropSize - floor($totalPixelCropSize)) < 0.5)) {
            // Previous even integer
            $num = 2 * round($totalPixelCropSize / 2.0);
        } else {
            // Next even integer
            $num = (int) ceil($totalPixelCropSize);
            $num += $num % 2;
        }
        return (float) $num;
    }

    /**
     * Calculate HORIZONTAL (left and right) crop value for primary, cast, episode, recommendations and mainphoto images
     * Output is for portrait images!
     * @param int $fullImageWidth the width in pixels of the large original image
     * @param int $fullImageHeight the height in pixels of the large original image
     * @param int $newImageWidth the width in pixels of the desired cropt/resized thumb image
     * @param int $newImageHeight the height in pixels of the desired cropt/resized thumb image
     * @see IMDB page / (TitlePage)
     */
    public function thumbUrlCropParameter(int $fullImageWidth, int $fullImageHeight, int $newImageWidth, int $newImageHeight): float
    {
        $newScalefactor = $fullImageHeight / $newImageHeight;
        $scaledWidth = $fullImageWidth / $newScalefactor;
        $totalPixelCropSize = $scaledWidth - $newImageWidth;
        $cropValue = max($this->roundInteger($totalPixelCropSize) / 2, 0);
        return $cropValue;
    }

    /**
     * Calculate VERTICAL (Top and bottom)crop value for primary, cast, episode and recommendations images
     * Output is for landscape images!
     * @param int $fullImageWidth the width in pixels of the large original image
     * @param int $fullImageHeight the height in pixels of the large original image
     * @param int $newImageWidth the width in pixels of the desired cropt/resized thumb image
     * @param int $newImageHeight the height in pixels of the desired cropt/resized thumb image
     * @see IMDB page / (TitlePage)
     */
    public function thumbUrlCropParameterVertical(int $fullImageWidth, int $fullImageHeight, int $newImageWidth, int $newImageHeight): float
    {
        $newScalefactor = $fullImageWidth / $newImageWidth;
        $scaledHeight = $fullImageHeight / $newScalefactor;
        $totalPixelCropSize = $scaledHeight - $newImageHeight;
        $cropValue = max($this->roundInteger($totalPixelCropSize) / 2, 0);
        return $cropValue;
    }

    /**
     * Calculate new width for mainphoto thumbnail images
     * @param int $fullImageWidth the width in pixels of the large original image
     * @param int $fullImageHeight the height in pixels of the large original image
     * @param int $newImageHeight the height in pixels of the desired cropt/resized thumb image
     * @return float newImageWidth
     */
    public function thumbUrlNewWidth(int $fullImageWidth, int $fullImageHeight, int $newImageHeight): float
    {
        $newScalefactor = $fullImageHeight / $newImageHeight;
        $rawNewWidth = $fullImageWidth / $newScalefactor;
        return ceil($rawNewWidth);
    }
}
