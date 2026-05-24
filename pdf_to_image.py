import fitz  # PyMuPDF
import sys

pdf_path = sys.argv[1]
output_prefix = sys.argv[2]

doc = fitz.open(pdf_path)
for page_num in range(len(doc)):
    page = doc[page_num]
    # Render at 2x scale for better quality
    mat = fitz.Matrix(2, 2)
    pix = page.get_pixmap(matrix=mat)
    output_path = f"{output_prefix}_page{page_num + 1}.png"
    pix.save(output_path)
    print(f"Saved: {output_path}")

doc.close()
