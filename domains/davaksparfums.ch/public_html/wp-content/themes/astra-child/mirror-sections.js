document.addEventListener('DOMContentLoaded', () => {
	const sections = [
		{
			outer: 'uagb-block-c74b22d1',
			grid: 'uagb-block-d6c123ab',
			media: 'uagb-block-ae12f34b',
			copy: 'uagb-block-b19e8c3d',
			reversed: false,
		},
		{
			outer: 'uagb-block-3644c970',
			grid: 'uagb-block-6c2341c7',
			media: 'uagb-block-a0289cde',
			copy: 'uagb-block-a1fc7617',
			reversed: true,
		},
	];

	sections.forEach(({ outer, grid, media, copy, reversed }) => {
		const outerEl = document.querySelector(`.${outer}`);
		const gridEl = document.querySelector(`.${grid}`);
		const mediaEl = document.querySelector(`.${media}`);
		const copyEl = document.querySelector(`.${copy}`);

		if (outerEl) {
			outerEl.classList.add('mirror-shell');
		}

		if (gridEl) {
			gridEl.classList.add('mirror-section');
			if (reversed) {
				gridEl.classList.add('is-reversed');
			}
		}

		if (mediaEl) {
			mediaEl.classList.add('mirror-media');
		}

		if (copyEl) {
			copyEl.classList.add('mirror-copy');
			const eyebrow = copyEl.querySelector('h6');
			if (eyebrow) {
				eyebrow.classList.add('eyebrow');
			}
		}
	});
});
