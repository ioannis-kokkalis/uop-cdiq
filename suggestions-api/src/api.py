import shutil
from transformers import pipeline
from fastapi import FastAPI, UploadFile, File, HTTPException, BackgroundTasks
import logging
import os
from TextExtractor import TextExtractor
from TextPreprocessor import TextPreprocessor
from Translator import Translator
from langdetect import detect
from queue import Queue
from fastapi.responses import JSONResponse

app = FastAPI()

task_queue = Queue()
is_processing = False

# Set up extensive logging
logging.basicConfig(
    level=logging.INFO,
    format="%(asctime)s [%(levelname)s] %(message)s",
    handlers=[logging.StreamHandler()]
)

# Load the summarizer and classifier
logging.info("Loading the summarizer...")
summarizer = pipeline("summarization", model="philschmid/bart-large-cnn-samsum", truncation=True)
logging.info("Done!")
logging.info("Loading the classifier...")
classifier = pipeline("zero-shot-classification", model="facebook/bart-large-mnli")
logging.info("Done!")
translator = Translator()
candidate_labels = ['finance', 'healthcare', 'information & technology', 'business management']




def process_resume(file_path):
    try:
        # Extract text from the resume
        text_extractor = TextExtractor(file_path)
        text = text_extractor.extract_text()
        if not text:
            raise HTTPException(status_code=500, detail="Failed to extract text from the resume")
        logging.info(f"Extracted text from the resume: {text}")
        
        translated_text = text.split()

        if detect(text) == 'el':
            try:
                # Translate the text to english
                if len(translated_text) > 1024:
                    translated_text = ' '.join(translated_text[:1023])
                    translated_text = translator.translate_large_text_to_english(translated_text)
                else:
                    translated_text = ' '.join(translated_text)
                    translated_text = translator.translate_to_english(text)

                logging.info(f"Translated text to english: {translated_text}")
            except HTTPException as e:
                raise HTTPException(status_code=e.status_code, detail=e.detail)
        else:
            translated_text = ' '.join(translated_text)

        # Preprocess the text
        text_preprocessor = TextPreprocessor(translated_text)
        preprocessed_text = text_preprocessor.preprocess_text()
        if not preprocessed_text:
            raise HTTPException(status_code=500, detail="Failed to preprocess the text")
        logging.info(f"Preprocessed text: {preprocessed_text}")

        # Summarize the text
        summary = summarizer(preprocessed_text, max_length=100, min_length=30)
        logging.info(f"Summary from the preprocessed text: {summary}")

        if not summary:
            raise HTTPException(status_code=500, detail="Failed to summarize the text")

        # Classify the summary
        output = classifier(summary[0]['summary_text'], candidate_labels)
        if not output:
            raise HTTPException(status_code=500, detail="Failed to classify the summary")
        logging.info(f"Classification: {output}")

        return {
            "summary": summary[0]['summary_text'],
            "classification": list(zip(output['labels'], output['scores']))
        }

    except FileNotFoundError:
        raise HTTPException(status_code=400, detail="File not found")
    except Exception as e:
        logging.error(f"An error occurred: {e}")
        raise HTTPException(status_code=500, detail="Internal Server Error")


def process_queue():
    while not task_queue.empty():
        # Log contents of the queue before processing
        logging.info("Queue contents before processing: %s", list(task_queue.queue))

        file_path = task_queue.get()
        result = process_resume(file_path)
        task_queue.task_done()
        # Log contents of the queue after processing
        logging.info("Queue contents after processing: %s", list(task_queue.queue))

        # save the result to a file 
        user_id = os.path.basename(file_path).split(".")[0]
        result_file_path = f"/tmp/{user_id}.result"
        with open(result_file_path, "w") as f:
            f.write(str(result))

        # Delete the file
        os.remove(file_path)



@app.post("/classify_resume")
async def classify_resume(background_tasks: BackgroundTasks, file: UploadFile = File(...)):
    # Save the file to a temporary location
    file_path = f"/tmp/{file.filename}"
    with open(file_path, "wb") as buffer:
        shutil.copyfileobj(file.file, buffer)
    
    # Ensure file is saved properly
    if not os.path.exists(file_path):
        raise HTTPException(status_code=500, detail="Failed to save the uploaded file")
    logging.info(f"Successfully saved file to {file_path}")

    # Add the file to the queue
    task_queue.put(file_path)
    background_tasks.add_task(process_queue)

    return {"message": "Processing the resume. Check later for results."}
    
@app.get("/queue_position/{user_id}")
async def queue_position(user_id: str):
    # Log contents of the queue before querying
    logging.info("Queue contents before querying: %s", list(task_queue.queue))

    if f"{user_id}.pdf" not in [os.path.basename(item) for item in list(task_queue.queue)]:
        position = None
    else:
        position = "Processed"


    # Log the queue position
    logging.info("Queue position for user %s: %s", user_id, position)

    return JSONResponse(content={"queue_position" : position})


@app.get("/result/{user_id}")
async def results(user_id: str):
    user_id = os.path.basename(user_id).split(".")[0]
    result_file_path = f"/tmp/{user_id}.result"
    if os.path.exists(result_file_path):
        with open(result_file_path, "r") as f:
            result = f.read()
        os.remove(result_file_path)
        return result
    else:
        raise HTTPException(status_code=404, detail="Result not found")


